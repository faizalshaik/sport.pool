<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class UserBettingApi extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->timeZone = 'Africa/Lagos';
		date_default_timezone_set($this->timeZone);
		$this->load->helper('url');
		$this->load->model('Base_model');
		$this->load->model('Calc_model');		
		
		$this->load->model('FilterBet_model');
		$this->load->model('FormatFixture_model');
		$this->load->model('FormatBetEvents_model');
		$this->load->model('Setting_model');
		$this->load->model('OptionGroup_model');		
		$this->load->model('Week_model');
		$this->load->model('TerminalPrinting_model');				
		
		
		$this->tblBet = 'tbl_bet';
		$this->tblBetBook = 'tbl_bet_book';
		$this->tblSport = 'tbl_sport';
		$this->tblOption = 'tbl_option';
	}
	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}	
	protected function reply_data($status, $message, $data)
	{
		return array('status'=>$status, 'message'=>$message, 'data'=>$data);
	}	


	function find_fixture($lst, $qbet, $date)
	{
		foreach($lst as $itm)
		{
			if($itm->qbet == $qbet && $itm->date == $date)
				return $itm;
		}
		return null;
	}	


	function data_by_id($lst, $id)
	{
		foreach($lst as $itm)
		{
			if($itm->id == $id)
				return $itm;
		}
		return null;
	}

	function data_by_key($lst, $key)
	{
		foreach($lst as $itm)
		{
			if($itm->key == $key)
				return $itm;
		}
		return null;
	}	

	function get_option_group($optGroups, $optId)
	{
		foreach($optGroups as $grp)
		{
			if($grp['id'] !=1)
			{
				foreach($grp['options'] as $opt)
				{
					if($opt['id'] == $optId)
						return $grp;
				}	
			}
		}
		return null;
	}

	private function get_bonus_percent($bonuses, $events, $minprize)
	{
		foreach($bonuses as $bonus)
		{
			if($bonus->events == $events)
			{
				if($minprize >= $bonus->min_prize)
					return $bonus->percent;
			}
		}
		return 0;
	}	

	private function make_bet_internal($user, $request)
	{
		$today = date('Y-m-d');
		$cur_week = $this->Base_model->getRow('tbl_week', ['from <=' =>$today, 'to >=' =>$today]);
		$bonuses = $this->Base_model->getDatas('tbl_bonus', null);		

		$events = $request['events'];
		$under = $request['under'];
		$amount = $request['amount'];

		$admin = $this->Base_model->getRow('tbl_user', ['role'=>'admin']);

		//check bet's validate
		$settings = $this->Setting_model->settings();
		if($request['amount'] > intval($settings['max_stake']) )
		{
			return $this->reply_data(402, "Amount can't big than max stake ". $settings['max_stake'], null);
		}
		if(count($events) < intval($settings['min_events_per_ticket']) )
		{
			return $this->reply_data(402, 'Please choose at least '. $settings['min_events_per_ticket'].' evetns!', null); 
		}
		if(count($events) > intval($settings['max_events_per_ticket']) )
		{
			return $this->reply_data(402, 'Events count excced max events '. $settings['man_events_per_ticket'], null); 
		}


		if(count($events)==0 || $under == 0 || $under > count($events) || $amount <=0 )
		{
			return $this->reply_data(402, 'Invalid bet', null); 
		}

		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$user_id = $user->id;
		if($amount > $user->balance)
		{
			return $this->reply_data(402, 'Insufficient balance', null);
		}


		$last_bet_id = $user->last_bet_id;
		if ($last_bet_id >= 9999)
			$last_bet_id = 1;
		else
			$last_bet_id ++;
		
		// check events
		$optionGroups = $this->OptionGroup_model->get_option_groups();
		$options = $this->Base_model->getDatas('tbl_option', null);
		$leagues = [];
		$countries = [];
		foreach($sports as $sport)
		{
			$leagues[$sport->id] = $this->Base_model->getDatas('tbl_'.$sport->key.'_league', null);
			$countries[$sport->id] = $this->Base_model->getDatas('tbl_'.$sport->key.'_country', null);
		}


		$bet_expire = date('Y-m-d H:i:s');
		$gamelist = [];
		$bet_results = [];
		$total_odd = 1;
		for($i=0; $i<count($events); $i++)
		{
			$minprize = 1000;			
			$ev = $events[$i];			
			$qbet = $ev['event'];
			$sport_id = intval($qbet / 10000) + 1;
			$sport = $this->data_by_id($sports, $sport_id);
			if($sport == null)
				return $this->reply_data(402, 'Invalid qbet '.$sport_id, null);

			$events[$i]['sport'] = $sport->id;
			$events[$i]['sport_key'] = $sport->key;

			$tblFixture = 'tbl_'.$sport->key.'_fixture';
			$fixture = $this->Base_model->getRow($tblFixture, ['qbet'=>$ev['event'], 'week'=>$cur_week->week]);
			if($fixture==null)
			{
				return $this->reply_data(402, 'Include invalid event', null);
			}

			$option = $this->data_by_id($options, $ev['opt']);
			if($option==null)
			{
				return $this->reply_data(402, 'Include invalid option', null);
			}
			$expire = $fixture->date.' '.$fixture->time;
			if($bet_expire == '') $bet_expire = $expire;
			else if($expire < $bet_expire)
				$bet_expire = $expire;

			$odds = json_decode($fixture->odds, true);
			$prize = $odds[$option->key];
			if($prize==null)
			{
				return $this->reply_data(402, 'Include invalid option', null);
			}

			$opt_group = $this->get_option_group($optionGroups, $option->id);			
			if($opt_group == null)
			{
				return $this->reply_data(402, 'Include invalid option', null);
			}
			$option->group = $opt_group['short_name'];

			//apply odd limit and increase
			$prize += $opt_group['odd_increase'];
			if($opt_group['max_odd'] > 0 && $prize > $opt_group['max_odd'])
				$prize = $opt_group['max_odd'];

			$gamelist[] = ['key' => $fixture->key, 'state'=>'win', 'prize'=> $prize];
			$events[$i]['prize'] = $prize;
			$fixture_fmt = $this->FormatFixture_model->format($fixture);

			$fixture_fmt['league'] = '';
			$league = $this->data_by_key($leagues[$sport->id], $fixture->league_key);
			if($league)$fixture_fmt['league'] = $league->name;

			$fixture_fmt['country'] = '';
			$country = $this->data_by_key($countries[$sport->id], $league->country_key);
			if($country)$fixture_fmt['country'] = $country->name;
		

			$odds = json_decode($fixture->odds, true);
			$bet_results[] = ['sport'=> $sport, 'fixture'=> $fixture_fmt, 'option'=>$option, 'prize'=>$prize, 'state'=>'none', 'time'=>'full'];

			if($prize < $minprize) $minprize = $prize;

			$total_odd *= $prize;
		}

		//check total odd
		if($total_odd < floatval($settings['min_total_odd']))
		{
			return $this->reply_data(402, 'Please add more games', null);
		}
		if($total_odd > floatval($settings['max_total_odd']))
		{
			return $this->reply_data(402, 'Please remove some games', null);
		}

		$max_win = $this->Calc_model->calc_win($gamelist, $under, $amount, true);
		$bonus = 0;
		$bonus_percent = 0;		
		$type = 'Permutation';
		if($under == count($events))
		{
			$bonus_percent = $this->get_bonus_percent($bonuses, $under, $minprize);			
			$type = 'Direct';
		}

		if($user_id < 10000)
			$bet_id = sprintf('%u%04u', $user_id + 90000, $last_bet_id);
		else
			$bet_id = sprintf('%u%04u', $user_id, $last_bet_id);
		$data = [
			'user_id' => $user_id, 
			'bet_id'=>$bet_id, 
			'type' => $type,
			'under'=> $under, 
			'max_win' => $max_win,
			'bonus' => $bonus,
			'bonus_percent' => $bonus_percent,
			'amount' => $amount,
			'events'=>json_encode($events), 
			'event_count'=>count($events), 
			'results' => json_encode($bet_results),
			'expire_at' => $bet_expire,
			'insert_at'=>date('Y-m-d H:i:s'),
			'year' => $cur_week->year,
			'week' => $cur_week->week,
			'won_amount' => 0,
			'total_win' => 0
		];

		$new_bal = $user->balance - $amount;
		$new_bal_rcv = $admin->balance + $amount;

		$this->Base_model->insertData($this->tblBet, $data);
		$this->Base_model->updateData('tbl_user', ['id'=> $user_id], 
			['last_bet_id' => $last_bet_id, 'last_bet_time'=>date('Y-m-d H:i:s'), 'balance'=>$new_bal]);
		$this->Base_model->updateData('tbl_user', ['id'=> $admin->id], 
			['balance'=>$new_bal_rcv]);

		$this->Base_model->insertData('tbl_transaction',
			['user_id' => $user->id, 'date'=>date('Y-m-d H:i:s'), 
			'payer_id' => $user->id,
			'receiver_id' => $admin->id,
			'type'=>'bet', 'amount'=>$amount,
			'descr' => 'BetID: '.$bet_id,
			'org_balance' => $user->balance,
			'new_balance'=>$new_bal]);

		$this->Base_model->insertData('tbl_transaction',
			['user_id' => $admin->id, 'date'=>date('Y-m-d H:i:s'), 
			'payer_id' => $user->id,
			'receiver_id' => $admin->id,
			'type'=>'bet', 'amount'=>$amount,
			'descr' => 'BetID: '.$bet_id,
			'org_balance' => $admin->balance,
			'new_balance'=>$new_bal_rcv]);
		return $this->reply_data(200, 'ok', ['bet'=>$data, 'betid'=>$bet_id, 'balance'=>$new_bal]);
	}

	public function make_bet()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;
		$user = $this->Base_model->getRow('tbl_user', ['id'=>$userInfo->id]);
		if($user==null)
		{
			$this->reply(401, 'Invalid token', null);
			return;
		}
		$result = $this->make_bet_internal($user, $request);
		echo json_encode($result);
	}


	private function checkTerminalLogin($req)
	{
		$sn = $req['sn'];
		if($sn=="") {
			$this->reply(401, "sn required", null);
			return null;
		}

		$token = $req['token'];
		if($token=="")
		{
			$this->reply(401, "token required", null);
			return null;
		}

		$user = $this->Base_model->getRow('tbl_user', ['login'=>$sn]);
		if($user==null) {
			$this->reply(401, "sn does not exist", null);
			return null;
		}
		// if($user->status!=1) 
		// 	return $this->reply(401, "terminal is not allowed", null);

		if($token !=$user->token){
			$this->reply(401, "token mismatch", null);
			return null;
		}
		return $user;
	}

	public function make_bet_by_terminal()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkTerminalLogin($request);
		if($user==null)
			return;			
		$result = $this->make_bet_internal($user, $request);
		if($result['status']!=200)
		{
			echo json_encode($result);
			return;
		}

		$bet = json_decode(json_encode($result['data']['bet']), false);
		$lines = $this->TerminalPrinting_model->format_bet($user, $bet);
		$result['data']['bet'] = $lines;
		echo json_encode($result);
	}
	

	public function get_bet()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$bet = $this->Base_model->getRow('tbl_bet', ['bet_id' => $request->bet_id]);
		if($bet == null)
		{
			$this->reply(401, 'invalid bet_id', null);
			return;
		}
		$this->reply(200, 'ok', $bet);
	}	

	public function get_bet_for_print()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$bet = $this->Base_model->getRow('tbl_bet', ['bet_id' => $request->bet_id]);
		if($bet == null)
		{
			$this->reply(401, 'invalid bet_id', null);
			return;
		}

		$user = $this->Base_model->getRow('tbl_user', ['id'=>$bet->user_id]);
		if($user == null)	
		{
			$this->reply(401, 'Unknown user', null);
			return;
		}
		$this->reply(200, 'ok', ['bet'=>$bet, 'user'=>['login'=>$user->login, 'name'=>$user->surname.' '.$user->name]]);
	}	


		


	public function get_bet_list()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;

		if($userInfo->role !='user')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$filter = $request->filter;
		$filter->date_from = date('Y-m-d H:i:s', strtotime($filter->date_from));
		$filter->date_to = date('Y-m-d H:i:s', strtotime($filter->date_to));

		$res = [];
		$bets = $this->Base_model->getDatas('tbl_bet', ['insert_at >=' => $filter->date_from, 'insert_at <=' => $filter->date_to, 'user_id'=>$userInfo->id], 'insert_at');

		$t_amount = 0;
		$max_win_inprogress = 0;
		$t_void = 0;
		$t_won = 0;
		$t_net = 0;

		$index = 0;
		foreach($bets as $bet)
		{
			if($this->FilterBet_model->filter($bet, $filter, null, null, null) == false)continue;
			$index++;
			if($index < $filter->view_from)continue;
			$res[]= $bet;

			$t_amount += $bet->amount;
			if($bet->status ==1)
			{
				if($bet->bet_result=='none')
					$max_win_inprogress += $bet->max_win;
				else if($bet->bet_result=='won')
					$t_won += $bet->won_amount;
				else if($bet->bet_result=='lost')
					$t_net += $bet->amount;				
			}
			if($bet->status ==2)
				$t_void += $bet->amount;

			if(count($res) >= $filter->view_count)break;				
		}
		$this->reply(200, 'ok', ['bets'=>$res, 'summary'=>[$t_amount, $max_win_inprogress, $t_void, $t_won, $t_net]]);
	}	


	public function get_bet_report()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;

		if($userInfo->role !='user')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$filter = $request->filter;
		$filter->date_from = date('Y-m-d H:i:s', strtotime($filter->date_from));
		$filter->date_to = date('Y-m-d H:i:s', strtotime($filter->date_to));

		$res = [];
		$bets = $this->Base_model->getDatas('tbl_bet', ['insert_at >=' => $filter->date_from, 'insert_at <=' => $filter->date_to, 'user_id'=>$userInfo->id], 'insert_at');

		$start_day = date('Y-m-d', strtotime($filter->date_from));
		$end_day = date('Y-m-d', strtotime($filter->date_to));
		$days = [];

		$index = 0;
		while($start_day < $end_day)
		{
			$index++;
			if($index >= $filter->view_from)
				$days[] = $start_day;

			$datetime = new DateTime($start_day);
			$datetime->modify('+1 day');
			$start_day = $datetime->format('Y-m-d');	
			if(count($days) >= $filter->view_count)
				break;				
		}

		$tt_amount = 0;
		$tt_max_win_inprogress = 0;
		$tt_void = 0;
		$tt_won = 0;
		$tt_net = 0;

		$res = [];
		foreach($days as $day)
		{
			$t_amount = 0;
			$t_max_win_inprogress = 0;
			$t_void = 0;
			$t_won = 0;
			$t_net = 0;
			$t_bets = 0;
	
			foreach($bets as $bet)
			{
				$bet_day = date('Y-m-d', strtotime($bet->insert_at));
				if($bet_day != $day) continue;				
				if($this->FilterBet_model->filter($bet, $filter, null, null, null) == false)continue;

				$t_bets++;
				if($index < $filter->view_from)continue;
				$t_amount += $bet->amount;
				if($bet->status ==1)
				{					
					if($bet->bet_result=='none')
						$t_max_win_inprogress += $bet->max_win;
					else if($bet->bet_result=='won')
						$t_won += $bet->won_amount;
					else if($bet->bet_result=='lost')
						$t_net += $bet->amount;				
				}
				if($bet->status ==2)
					$t_void += $bet->amount;				
			}
			$res[] = ['date'=>$day, 
					'bets'=> $t_bets, 
					't_amount'=>$t_amount, 
					't_max_win_inprogress' => $t_max_win_inprogress,
					't_void' => $t_void,
					't_won' => $t_won,
					't_net' => $t_net];

			$tt_amount += $t_amount;
			$tt_max_win_inprogress += $t_max_win_inprogress;
			$tt_void += $t_void;
			$tt_won += $t_won;
			$tt_net += $t_net;
		}
		$this->reply(200, 'ok', ['days'=>$res, 'summary'=>[$tt_amount, $tt_max_win_inprogress, $tt_void, $tt_won, $tt_net]]);
	}	



	public function get_results()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);
		if ($userInfo == null) return;
		if ($userInfo->role != "user")
			return $this->reply(401, 'Permission denied!', null);

		$filter = $request->filter;
		$from = date('Y-m-d H:i:s', strtotime($filter->date_from));
		$to = date('Y-m-d H:i:s', strtotime($filter->date_to));
		$count = $filter->view_count;

		$sportId = $filter->sport;
		$sports = [];

		if($sportId >0)
		{
			$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sportId]);
			if($sport == null)
			{
				$this->reply(402, 'invalid sport', null);
				return;
			}
			$sports[] = $sport;
		}
		else
			$sports = $this->Base_model->getDatas('tbl_sport', null);


		$fixtures = [];
		foreach($sports as $sport)
		{
			$leagues = $this->Base_model->getDatas('tbl_'.$sport->key.'_league', null);		
			$countries = $this->Base_model->getDatas('tbl_'.$sport->key.'_country', null);
			$tblFixture = 'tbl_'.$sport->key.'_fixture';
			$datas = $this->Base_model->getDatas($tblFixture, ['status'=>'Finished', 'datetime>=' => $from, 'datetime <=' => $to], 'datetime','ASC');
			foreach($datas as $data)
			{
				$data->sport_id = $sport->id;
				$data->sport_name = $sport->name;
				$data->sport_key = $sport->key;

				$league = $this->data_by_key($leagues, $data->league_key);
				if($league)
					$data->league = $league->name;
				else
					$data->league = '';		
					
				$country = $this->data_by_key($countries, $data->country_key);
				if($country)
					$data->country = $country->name;
				else
					$data->country = '';	
				$fixtures[$data->datetime.'_'.$sport->id] = $data;
			}
		}		

		$res = [];
		$index = -1;
		foreach($fixtures as $key => $fixture)
		{
			$index++;
			if($index < $filter->view_from) continue;

			$ev = $this->FormatFixture_model->format($fixture);
			$ev['league'] = $fixture->league;
			$ev['country'] = $fixture->country;
			$ev['sport_id'] = $fixture->sport_id;
			$ev['sport_name'] = $fixture->sport_name;
			$ev['sport_key'] = $fixture->sport_key;
			$res[] = $ev;			
			if(count($res) >= $count)break;
		}
		$this->reply(200, 'ok', $res);
	}	
		

}