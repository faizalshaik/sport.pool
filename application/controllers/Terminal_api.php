<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Content-Type: application/json');

class Terminal_api extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Africa/Lagos');		
		$this->load->model('Base_model');
		$this->load->model('Calc_model');
		$this->load->model('FilterBet_model');
		$this->load->model('FormatFixture_model');
		$this->load->model('FormatBetEvents_model');
		$this->load->model('Setting_model');
		$this->load->model('OptionGroup_model');		
		$this->load->model('Week_model');
		$this->load->model('TerminalPrinting_model');

		$this->line_spliter = '-----------------------------------------';
	}

	public function test()
	{
		$lines = $this->TerminalPrinting_model->make_columned_contents(
			['12345', 'Real Mardrid FC', 'Baieron Munhen FC XXXX'],
			[40, 62, 62], '  '
		);		
		echo json_encode($lines);
	}

	public function reply($result, $message, $data)
	{
		$result = array('status'=>$result, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}

	private function checkLogin($req)
	{
		// $user = $this->Base_model->getRow('tbl_user', ['id'=>104]);
		// return 		$user;

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

	private function isSameBets($bet0, $bet1)	
	{
		if($bet0->type!=$bet1->type) return false;
		if($bet0->under!=$bet1->under) return false;
		if($bet0->event_count!=$bet1->event_count) return false;
		if($bet0->year!=$bet1->year) return false;
		if($bet0->week!=$bet1->week) return false;	

		$events0 = json_decode($bet0->events, false);
		$events1 = json_decode($bet1->events, false);

		foreach($events0 as $ev0)
		{
			$bSameEv = false;
			foreach($events1 as $ev1)
			{
				if($ev0->event == $ev1->event)
				{
					$bSameEv = true;
					if($ev0->opt != $ev1->opt)
						return false;
				}
			}
			if(!$bSameEv) return false;
		}
		return true;
	}

	private function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
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

	function get_odd_arr($odds, $options, $optionGroups)
	{
		$results = [];
		foreach($options as $opt)
		{
			if(!isset($odds[$opt->key]))
				continue;
			//find group
			$group = $this->get_option_group($optionGroups, $opt->id);
			if($group == null) continue;
			$prize = floatval($odds[$opt->key]);
			$prize += $group['odd_increase'];
			if($group['max_odd'] > 0 && $prize > $group['max_odd'])
				$prize = floatval($group['max_odd']);
			$results[] = [$opt->id, number_format($prize, 2)];
		}
		return $results;
	}

	function get_odd_arr_with_name($odds, $options, $optionGroups)
	{
		$results = [];
		foreach($options as $opt)
		{
			if(!isset($odds[$opt->key]))
				continue;
			//find group
			$group = $this->get_option_group($optionGroups, $opt->id);
			if($group == null) continue;
			$prize = floatval($odds[$opt->key]);
			$prize += $group['odd_increase'];
			if($group['max_odd'] > 0 && $prize > $group['max_odd'])
				$prize = $group['max_odd'];			
			$results[$opt->name] = $prize;
		}
		return $results;
	}	

	public function get_events_for_bet()
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;

		$bonuses = $this->Base_model->getDatas('tbl_bonus', null); 
		$sports = $this->Base_model->getDatas('tbl_sport', null);

		$from_dt = date('Y-m-d H:i:s');		
		$today = date('Y-m-d');
		$datetime = new DateTime($today);
		$datetime->modify('+1 day');
		$tomorrow = $datetime->format('Y-m-d');
		$cur_week = $this->Base_model->getRow('tbl_week', ['from <=' =>$today, 'to >=' =>$today]);
		if($tomorrow > $cur_week->to)
			$tomorrow = $cur_week->to;
		$to_dt = $tomorrow.' 23:59:59';

		$optionGroups = $this->OptionGroup_model->get_option_groups();
		$options = $this->Base_model->getDatas('tbl_option', null);

		$first_expire = $to_dt;
		$events = [];
		foreach($sports as $sport)
		{
			$fixtures = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', 
				['datetime >=' => $from_dt, 'datetime <=' => $to_dt, 'odds <>' => ''], 'datetime', 'ASC');

			foreach($fixtures as $fixture)
			{
				$odds = json_decode($fixture->odds, true);
				if($odds==null) continue;
				$odd_arr = $this->get_odd_arr($odds, $options, $optionGroups);
				if(count($odds)==0) continue;
				$events[] = ['q'=> $fixture->qbet, 'o'=>$odd_arr];

				if($fixture->datetime < $first_expire)
					$first_expire = $fixture->datetime;
				//if(count($events) >= 10) break;
			}
		}

		$opts = [];
		foreach($options as $opt)
		{
			$opts[] = ['i'=>$opt->id, 'n'=>$opt->name];
		}

		$bnuses = [];		
		foreach($bonuses as $bonus)
		{
			$bnuses[] = ['e'=>$bonus->events, 'b'=>$bonus->percent, 'p'=>number_format($bonus->min_prize, 2)];
		}

		$data_valid_secs = 600;
		if(count($events) > 0)
		{
			$data_valid_secs = strtotime($first_expire)	- strtotime($from_dt);
			$data_valid_secs = min($data_valid_secs, 600);
		}

		$this->reply(200, 'ok', ['events'=>$events, 'options'=>$opts, 'bonuses'=>$bnuses, 'data_valid_secs'=>$data_valid_secs]);
	}
	
	public function login() 
	{
		$req = json_decode(file_get_contents('php://input'), true);		
		$sn = $req['sn'];
		if($sn=="")return $this->reply(401, "sn required", null);
		$password = $req['password'];
		if($password=="")return $this->reply(401, "password required", null);

		$terminal = $this->Base_model->getRow('tbl_user', ['login'=>$sn]);
		if($terminal==null) return $this->reply(401, "sn dose not exist", null);
		//if($terminal->status!=1) return $this->reply(401, "terminal is not allowed", null);
		if(!password_verify($password, $terminal->password))return $this->reply(401, "wrong password", null);

		$token = $this->generateRandomString(32);
		$this->Base_model->updateData('tbl_user', array('id'=>$terminal->id), array('token'=>$token));

		$toady = date('Y-m-d');
		$curweek = $this->Base_model->getRow('tbl_week', ['from <=' =>$toady, 'to >=' => $toady] );
		if($curweek==null) return $this->reply(-1, "week does not exist.", null);

		$this->reply(200, "success", array(
			'sn'=>$sn,
			'token'=>$token,
			'week'=>$curweek->week,
			'start_at'=>date('m/d/y', strtotime($curweek->from)),
			'close_at'=>date('m/d/y', strtotime($curweek->to)),
			'balance'=>$terminal->balance
		));
	}

	public function results() 
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;

		$curWeekNo = $req['week'];
		$count_per_page = 20;
		$current_page = 1;
		if(isset($req['current_page']))
			$current_page = $req['current_page'];

		if($curWeekNo==0 || $curWeekNo=='')
		{
			$today = date('Y-m-d');
			$cur_week = $this->Base_model->getRow('tbl_week', ['from <=' =>$today, 'to >=' =>$today]);
			if($cur_week==null)
			{
				return $this->reply(402, "no current weekno", null);
			}	
			$curWeekNo = $cur_week->week;
		}

		$lines = [];
		$lines[] = '   Results     Week '.$curWeekNo;
		$sports = $this->Base_model->getDatas('tbl_sport', null);

		$fixtures = [];
		foreach($sports as $sport)
		{
			// $fixes = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', 			
			// 	['year' => $cur_week->year, 'week' => $curWeekNo, 'odds <>' => '', 'status'=>'Finished'], 'qbet', 'ASC');
			$fixes = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', 			
				['week' => $curWeekNo, 'odds <>' => '', 'status'=>'Finished'], 'qbet', 'ASC');

			foreach($fixes as $fix)
			{
				$fixtures[] = $fix;
			}
		}

		$count = 0;
		$start_idx = ($current_page-1) * $count_per_page;
		$last_page = intval(count($fixtures) / $count_per_page);
		if(count($fixtures) / $count_per_page != 0)
			$last_page++;

		if($current_page > $last_page) 
			return $this->reply(402, 'current_page mismatch', null);

		if(count($fixtures) % $count_per_page > 0)$last_page++;

		$lines[] = 'Page '.$current_page.' / '.$last_page;
		for($i=$start_idx; $i<count($fixtures); $i++)
		{
			$fixture = $fixtures[$i];			
			$lns = $this->TerminalPrinting_model->make_columned_contents([$fixture->qbet, $fixture->final_result], [70, 90], '  ');
			foreach($lns as $ln)$lines[] = $ln;
			$count++;
			if($count >= $count_per_page) break;
		}
		$this->reply(200, "ok", implode("\n", $lines));
	}
	
	public function reprint() 
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$bet_id = $req['bet_id'];

		$user = $this->checkLogin($req);
		if($user==null) return;
		if($bet_id=="" || $bet_id=="0")
		{
			if($user->id < 10000)
				$bet_id = sprintf('8%05u%04u', $user->id, $user->last_bet_id);
			else
				$bet_id = sprintf('%u%04u', $user->id + 90000, $user->last_bet_id);
		}

		$bet = $this->Base_model->getRow('tbl_bet', ['bet_id'=>$bet_id]);
		if($bet==null)
			return $this->reply(402, "betid dose not exist", null);

		$lines = "[copy]\n".$this->TerminalPrinting_model->format_bet($user, $bet);
		return $this->reply(200, 'ok', ['bet'=>$lines, 'balance'=>$user->balance]);
	}

	
	public function win_list() 
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;
		$agent = $this->Base_model->getRow('tbl_user', ['id'=>$user->parent_id]);
		if($agent==null)		
			return $this->reply(402, 'no agent', null);

		$count_per_page = 20;
		$current_page = 1;
		if(isset($req['current_page']))
			$current_page = $req['current_page'];

		if($current_page <= 0) return $this->reply(402, 'current_page mismatch', null);
		
		$curWeekNo = $req['week'];
		$week = null;
		if($curWeekNo==0 || $curWeekNo =='') 
		{
			$today = date('Y-m-d');
			$week = $this->Base_model->getRow('tbl_week', ['from <=' => $today, 'to >=' => $today]);
		}
		else
		{
			$this_year = date('Y');
			$week = $this->Base_model->getRow('tbl_week', ['year' => $this_year, 'week' => $curWeekNo]);
		}			
		if($week==null) return $this->reply(402, 'no current weekno', null);

		$cond = ['user_id'=>$user->id, 'bet_result'=>'won', 'year'=>$week->year, 'week'=>$week->week];
		$bet_id = $req['bet_id'];
		if($bet_id !="") $cond['bet_id'] = $bet_id;


		$lines = [];
		$lines[] = '         BetTwoStar';
		$lines[] = '         win-list';
		$lns = $this->TerminalPrinting_model->make_columned_contents(['Terminal:', $user->login], [70, 95], '  ');
		foreach($lns as $ln) $lines[]=$ln;

		$lns = $this->TerminalPrinting_model->make_columned_contents(['Agent:', $agent->login], [70, 95], '  ');
		foreach($lns as $ln) $lines[]=$ln;

		$lns = $this->TerminalPrinting_model->make_columned_contents(['Week:', $week->week], [70, 95], '  ');
		foreach($lns as $ln) $lines[]=$ln;


		$bets = $this->Base_model->getDatas('tbl_bet', $cond, 'bet_id');
		$totalPage = count($bets)/$count_per_page;
		if(count($bets) % $count_per_page) $totalPage++;
		if($current_page > $totalPage)
		{
			if($totalPage > 0)
				return $this->reply(402, 'current_page mismatch', null);
			else
				return $this->reply(402, 'There is no win list', null);
		}
			

		$start_idx = ($current_page -1) * $count_per_page;
		$total = 0;
		$count = 0;
		for($i=$start_idx; $i<count($bets); $i++)
		{
			$bet = $bets[$i];
			$lns = $this->TerminalPrinting_model->make_columned_contents([$bet->bet_id, '# '.number_format($bet->won_amount, 2)], [80, 90], ' ');
			foreach($lns as $ln) $lines[] = $ln;
			$total += $bet->won_amount;
			$count++;
			if($count >= $count_per_page) break;
		}

		$lines[] = '';
		$lns = $this->TerminalPrinting_model->make_columned_contents(['Total:', '# '.number_format($total, 2)], [70, 95], '  ');
		foreach($lns as $ln) $lines[]=$ln;
		$this->reply(200, "ok", implode("\n", $lines));
	}	

	public function report() 
	{
	   $req = json_decode(file_get_contents('php://input'), true);
	   $user = $this->checkLogin($req);
	   if($user==null) return;
	   $agent = $this->Base_model->getRow('tbl_user', ['id'=>$user->parent_id]);	   
	   if($agent==null)		
		   return $this->reply(402, 'no agent', null);


	   $curWeekNo = $req['week'];
	   $week = null;
	   if($curWeekNo==0 || $curWeekNo =='') 
	   {
		   $today = date('Y-m-d');
		   $week = $this->Base_model->getRow('tbl_week', ['from <=' => $today, 'to >=' => $today]);
	   }
	   else
	   {
		   $this_year = date('Y');
		   $week = $this->Base_model->getRow('tbl_week', ['year' => $this_year, 'week' => $curWeekNo]);
	   }			
	   if($week==null) return $this->reply(402, 'no current weekno', null);

	   $bets = $this->Base_model->getDatas('tbl_bet', ['user_id'=>$user->id, 'year'=>$week->year, 'week'=>$week->week]);

	   $t_amount = 0;
	   $t_max_win = 0;
	   $t_win = 0;

	   $reports = [];
	   foreach($bets as $bet)
	   {
			$t_amount += $bet->amount;
			$under = 0;
			if($bet->type == 'Permutation')
				$under = $bet->under;				
			
			$entry = null;
			if(isset($reports[$under]))
				$entry = $reports[$under];
			else
				$entry = ['count'=>0, 'amount'=>0, 'bonus'=>0, 'payable'=>0, 'total_win'=>0, 'lost'=>0, 'void'=>0, 'void_count'=>0];

			$entry['count'] ++;
			$entry['amount'] += $bet->amount;			
			$entry['bonus'] += $bet->bonus;
			if($bet->status==2)
			{
				$entry['void'] += $bet->amount;	
				$entry['void_count'] ++;
			}
			else
			{
				if($bet->bet_result=='none')
				{
					$entry['max_win'] += $bet->max_win;
					$t_max_win += $bet->max_win;
				}
				else if($bet->bet_result=='won')
				{
					$entry['won'] += $bet->won_amount;
					$entry['total_win'] += $bet->total_win;
					$t_win += $bet->total_win;
				}
				else
					$entry['lost'] += $bet->amount;
			}
			$reports[$under] = $entry;
	   }


	   $lines[] = '         Report Week '. $week->week;
	   $lns = $this->TerminalPrinting_model->make_columned_contents(['Terminal:', $user->login], [70, 95], '  ');
	   foreach($lns as $ln) $lines[]=$ln;

	   $lns = $this->TerminalPrinting_model->make_columned_contents(['Agent:', $agent->login], [70, 95], '  ');
	   foreach($lns as $ln) $lines[]=$ln;

	   $lns = $this->TerminalPrinting_model->make_columned_contents(['Week:', $week->week], [70, 95], '  ');
	   foreach($lns as $ln) $lines[]=$ln;

	   $lns = $this->TerminalPrinting_model->make_columned_contents(['Close at:', date('m/d/y', strtotime($week->to))], [70, 95], '  ');
	   foreach($lns as $ln) $lines[]=$ln;

	   $total = 0;
	   $lines[] = '         Gross';
	   foreach($reports as $under=>$sumer)
	   {
		   if($under==0)
		   {
				$lns = $this->TerminalPrinting_model->make_columned_contents(
					['D', '# '.number_format($sumer['amount']), number_format($sumer['max_win'])], [22, 60, 74], ' ');
				foreach($lns as $ln) $lines[]=$ln;
		   }
		   else
		   {
				$lns = $this->TerminalPrinting_model->make_columned_contents(
					['U'.$under, '# '.number_format($sumer['amount']), number_format($sumer['max_win'])], [22, 60, 74], ' ');
				foreach($lns as $ln) $lines[]=$ln;
		   }
		   $total += $sumer['max_win'];
	   }
	   $lines[] = 'Total Payable # '.number_format($total, 2);

	   $total = 0;
	   $lines[] = '';
	   $lines[] = '         Winning';
	   foreach($reports as $under=>$sumer)
	   {
		   if($under==0)
		   {
				$lns = $this->TerminalPrinting_model->make_columned_contents(
					['D', '# '.number_format($sumer['won'], 2)], [42, 120], ' ');
				foreach($lns as $ln) $lines[]=$ln;
		   }
		   else
		   {
				$lns = $this->TerminalPrinting_model->make_columned_contents(
					['U'.$under, '# '.number_format($sumer['won'], 2)], [42, 120], ' ');
				foreach($lns as $ln) $lines[]=$ln;
		   }
		   $total += $sumer['won'];
	   }
	   $lines[] = 'Total Wining # '.number_format($total, 2);
	   $this->reply(200, 'ok', implode("\n", $lines));
	}

	public function availability()
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;
		$qbet = $req['qbet'];
		if($qbet == '' || $qbet==0)
			return $this->reply(402, 'invalid qbet!', null);

		$sport_id = intval($qbet / 10000) + 1;
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport == null)
			return $this->reply(402, 'invalid qbet!', null);

		$today = date('Y-m-d');
		$week = $this->Base_model->getRow('tbl_week', ['from <=' => $today, 'to >=' => $today]);
		if($week == null)
			return $this->reply(402, 'invalid current week!', null);

		$fixture = $this->Base_model->getRow('tbl_'.$sport->key.'_fixture', ['qbet'=>$qbet, 'year'=>$week->year, 'week'=>$week->week]);
		if($fixture==null)
			return $this->reply(402, 'invalid qbet!', null);

		$lines = [];
		$nlns = $this->TerminalPrinting_model->make_columned_contents(
			[strval($fixture->qbet), $fixture->home_team, $fixture->away_team], 
			[40, 62, 62], '  ');
		foreach($nlns as $line)$lines[] = $line;
		$lines[]= 'Date  '.date('m/d/y g:i A');

		$odds = json_decode($fixture->odds, true);
		if($odds==null)
		{
			$lines[]= 'There is no market info!';
		}
		else
		{
			$odd_lines = [];
			$options = $this->Base_model->getDatas('tbl_option', null);	
			$optionGroups = $this->OptionGroup_model->get_option_groups();	
			$odds = $this->get_odd_arr_with_name($odds, $options, $optionGroups);
			foreach($odds as $odd_name => $prize)
			{
				$odd_lines[] = $odd_name.'='.number_format(floatval($prize), 2);
			}	
			$lines[]= implode(', ', $odd_lines);
		}
		$this->reply(200, 'ok', implode("\n", $lines));
	}

	public function credit_limit() {
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;	
		$this->reply(200, 'ok', ['credit_limit' => $user->balance]);
	}		

	public function sign_list() {
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;	

		$options = $this->Base_model->getDatas('tbl_option', null);
		$lines = [];
		$lines[] = "          Sign List";
		foreach($options as $opt)
		{
			$lines[] = $opt->id. '   '.$opt->name;
		}
		$this->reply(200, 'ok', implode("\n", $lines));
	}		


	public function logout() {
		$req = json_decode(file_get_contents('php://input'), true);

		$user = $this->checkLogin($req);
		if($user==null) return;
		$token = $this->generateRandomString(32);
		$this->Base_model->updateData('tbl_user', ['id'=>$user->id], ['token'=>$token]);
		$this->reply(200, 'ok',null);
	}

	public function void_bet()
	{
		$req = json_decode(file_get_contents('php://input'), true);

		$user = $this->checkLogin($req);
		if($user==null) return;
		//check user
		$betId = $req['bet_id'];
		
		$bet = $this->Base_model->getRow('tbl_bet', ['bet_id'=>$betId, 'user_id'=>$user->id]);
		if($bet==null)
			return $this->reply(402, "bet_id dose not exist", null);

		$today = date('Y-m-d');
		$week = $this->Base_model->getRow('tbl_week', ['from <=' => $today, 'to >=' => $today]);
		if($week==null)
			return $this->reply(402, "week dose not exist", null);

		$curDate = new DateTime();
		if($curDate->format('Y-m-d H:i:s') > $week->to. ' 23:59:59')
			return $this->reply(1004, "bet does not change in past week", null);


		$setting = $this->Setting_model->settings();
		$void_bet = $setting['void_bet'];

		$curDate->sub(new DateInterval('PT'.$void_bet.'H'));
		if($curDate->format('Y-m-d H:i:s') > $bet->insert_at)
			return $this->reply(1003, "void time passed", null);

		// $gamelists = $this->Game_model->getDatas(array('week_no'=>$week->week_no, 'status'=>1));
		// if($gamelists==null)
		// 	return $this->reply(-1, "game does not exist", null);
		// $missed = $this->checkMissedGames($gamelists, $bet);
		// if(count($missed)>0)
		// 	return $this->reply(1003, "void failed", null);

		//save deelte request
		// $row = $this->DeleteRequest_model->getRow(array('bet_id'=>$bet['Id']));
		// if($row!=null)
		// 	return $this->reply(1003, "already requested", null);

		// $this->DeleteRequest_model->insertData(array('bet_id'=>$bet['Id'], 'terminal_id'=>$terminal->Id, 'agent_id'=>$terminal->agent_id));

		//update bet status
		$this->Base_model->updateData('tbl_bet', ['bet_id'=>$betId], ['status'=>2]);

		// $commission = 0 ;
		// $under = $this->Under_model->getRow(['under'=>$bet['under']]);
		// if($under!=null) $commission = $under->commission;
		//$this->calcTerminalSummary($terminal->Id, $terminal->agent_id, $bet['under'], $commission, $bet['week']);		
		return $this->reply(200, "success", null);
	}

	public function password_change() 
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;

		$newPasswd = $req['new_password'];
		if($newPasswd=="")
			return $this->reply(402, 'enter new password', null);	

		$passwd = password_hash($newPasswd, PASSWORD_DEFAULT);
		$this->Base_model->updateData('tbl_user', ['id'=>$user->id], ['password'=>$passwd]);
		return $this->reply(200, 'success', null);
	}
	
	public function fixtures()
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;

		$count_per_page = 10;
		$current_page = 1;
		if(isset($req['current_page']))
			$current_page = $req['current_page'];
		if($current_page <= 0) return $this->reply(402, 'current_page mismatch', null);


		$today = date('Y-m-d');
		$cur_week = $this->Base_model->getRow('tbl_week', ['from <=' => $today, 'to >=' => $today]);
		if($cur_week==null)
			return $this->reply(402, "week dose not exist", null);

		$from_dt = date('Y-m-d H:i:s');		
		$datetime = new DateTime($today);
		$datetime->modify('+1 day');
		$tomorrow = $datetime->format('Y-m-d');
		if($tomorrow > $cur_week->to)
			$tomorrow = $cur_week->to;
		$to_dt = $tomorrow.' 23:59:59';

		$sports = $this->Base_model->getDatas('tbl_sport', null);		
		$lines = [];

		$options = $this->Base_model->getDatas('tbl_option', null);
		$count = 0;
		$start_idx = ($current_page -1) * $count_per_page;

		$fixtures = [];		
		foreach($sports as $sport)
		{
			$fixes = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', 
				['datetime >=' => $from_dt, 'datetime <=' => $to_dt, 'odds <>' => ''], 'datetime', 'ASC');
			foreach($fixes as $fix) $fixtures[] = $fix;
		}
		$last_page = intval(count($fixtures) / $count_per_page);		
		if(count($fixtures) % $count_per_page > 0)$last_page++;

		if($current_page > $last_page)
			return $this->reply(402, 'current_page mismatch', null);
		
		$lines[] = '   Fixtures     Week '. $cur_week->week;
		$lines[] = 'Page   '.$current_page.' / '.$last_page;
		for($i=$start_idx; $i<count($fixtures); $i++)
		{
			$fixture = $fixtures[$i];
			$odds = json_decode($fixture->odds, true);
			if($odds==null) continue;

			$odd_lines = [];
			$optionGroups = $this->OptionGroup_model->get_option_groups();	
			$odds = $this->get_odd_arr_with_name($odds, $options, $optionGroups);
			if(count($odds)==0) continue;
			foreach($odds as $odd_name => $prize)
			{
				$odd_lines[] = $odd_name.'='.number_format(floatval($prize), 2);
			}
			$nlns = $this->TerminalPrinting_model->make_columned_contents(
				[strval($fixture->qbet), $fixture->home_team, $fixture->away_team], 
				[40, 62, 62], ' ');
			foreach($nlns as $line)$lines[] = $line;								
			$lines[]= implode(', ', $odd_lines);	
			$lines[]= $this->line_spliter;

			$count ++;
			if($count >= $count_per_page)break;
		}

		$this->reply(200, 'ok', implode("\n", $lines));
	}
	
	public function void_list() 
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;

		$curWeekNo = $req['week'];
		$week = null;
		if($curWeekNo==0 || $curWeekNo =='') 
		{
			$today = date('Y-m-d');
			$week = $this->Base_model->getRow('tbl_week', ['from <=' => $today, 'to >=' => $today]);
		}
		else
		{
			$this_year = date('Y');
			$week = $this->Base_model->getRow('tbl_week', ['year' => $this_year, 'week' => $curWeekNo]);
		}			
		if($week==null) return $this->reply(402, 'no current weekno', null);

		$bets = $this->Base_model->getDatas('tbl_bet', ['user_id'=>$user->id, 'week'=>$week->week, 'year'=>$week->year, 'status'=>2]);

		// $agentId = "";
		// $agent=$this->User_model->getRow(array('Id'=>$terminal->agent_id));
		// if($agent!=null) $agentId= $agent->user_id;

		$lines = [];
		$lines[] = '      Week '.$week->week;
		$lines[] = '      Void List';
		foreach($bets as $bet)
		{
			$lines[] = $bet->bet_id.'    #'.number_format($bet->amount);
		}
		//$this->reply(1, 'success', array('week'=>$curWeekNo, 'agent_id'=>$agentId, 'void_list'=>$results));
		$this->reply(200, 'success', implode("\n", $lines));
	}


	public function search() 
	{
		$req = json_decode(file_get_contents('php://input'), true);
				
		$user = $this->checkLogin($req);
		if($user==null) return;

		$curWeekNo = 0;
		if(isset($req['week']))
			$curWeekNo = $req['week'];

		$week = null;
		if($curWeekNo==0 || $curWeekNo =='') 
		{
			$today = date('Y-m-d');
			$week = $this->Base_model->getRow('tbl_week', ['from <=' => $today, 'to >=' => $today]);
		}
		else
		{
			$this_year = date('Y');
			$week = $this->Base_model->getRow('tbl_week', ['year' => $this_year, 'week' => $curWeekNo]);
		}			
		if($week==null) return $this->reply(402, 'no current weekno', null);


		$searchWord = $req['searchword'];
		if($searchWord=="")return $this->reply(402, 'no search word', null);

		$cond = array('user_id'=>$user->id, 'week'=>$week->week, 'year'=>$week->year);
		// if($isTicket==1)$cond['ticket_no'] = $searchWord;
		// else $cond['bet_id'] = $searchWord;

		$lines = [];	

		$lines[]='      Search - Week '.$week->week;
		$count = 0;
		$bets = $this->Base_model->getDatas('tbl_bet', $cond, 'bet_id');
		for($i = 0; $i<count($bets); $i++)
		{
			$strbet = strval($bets[$i]->bet_id);
			if(stristr($bets[$i]->bet_id, $strbet)===FALSE) continue;
			
			if($bets[$i]->status ==2)
				$lines[] = $bets[$i]->bet_id.'=> #'.number_format($bets[$i]->amount).' void';
			else if($bets[$i]->status ==1)
			{
				if($bets[$i]->bet_result == 'lost')
				{
					$lines[] = $bets[$i]->bet_id.'=> #'.number_format($bets[$i]->amount);
					$lines[] = '          pos.win=#'.number_format($bets[$i]->max_win).' lost';
				}
				else if($bets[$i]->bet_result == 'won')
				{
					$lines[] = $bets[$i]->bet_id.'=> #'.number_format($bets[$i]->amount);					
					$lines[] = '          won=#'.number_format($bets[$i]->won_amount).' won';
				}
				else
				{
					$lines[] = $bets[$i]->bet_id.'=> #'.number_format($bets[$i]->amount);
					$lines[] = '          pos.win=#'.number_format($bets[$i]->max_win).' inproc';
				}
			}
			$count ++;
			if($count >=10) break;
		}
		$this->reply(200, 'success', implode("\n", $lines));
	}	

	
	public function ticket_list() 
	{
		$req = json_decode(file_get_contents('php://input'), true);
		$user = $this->checkLogin($req);
		if($user==null) return;
		$today = date('Y-m-d');
		$week = $this->Base_model->getRow('tbl_week', ['from <=' => $today, 'to >=' => $today]);
		if($week == null)
		{
			return $this->reply(402, 'no current weekno', null);
		}

		$bets = $this->Base_model->getDatas('tbl_bet', ['user_id'=>$user->id, 'year'=>$week->year, 'week'=>$week->week],
			'insert_at', 'DESC');

		$lines = [];
		$lines[] = '            Ticket list';
		$count = 0;
		foreach($bets as $bet)
		{				
			//$lines[]=$bet->bet_id. '  '. date('m/d/y g:i a', strtotime($bet->insert_at));
			$lines[]=$bet->bet_id;
			$count++;
			if($count >=7) break;
		}
		$this->reply(200, 'ok', implode("\n", $lines));
	}
	

}