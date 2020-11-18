<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class AdminBet_api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Africa/Lagos');
		$this->load->model('User_model');
		$this->load->model('Base_model');
		$this->load->model('FormatBetEvents_model');	
		$this->load->model('FilterBet_model');			
	}

	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}
	
	
	public function get_bet_list()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$filter = $request->filter;
		$filter->date_from = date('Y-m-d H:i:s', strtotime($filter->date_from));
		$filter->date_to = date('Y-m-d H:i:s', strtotime($filter->date_to));

		$admins = $this->Base_model->getDatas('tbl_user', ['role <>' => 'user']);
		$loginUser = $this->data_by_id($admins, $userInfo->id);
		if($loginUser == null)
		{
			$this->reply(401, "permission is not allowed 0", null);
			return;
		}

		$usrs = $this->Base_model->getDatas('tbl_user', ['role' => 'user']);
		$users = [];
		foreach($usrs as $usr)$users[$usr->id] = $usr;

		$res = [];
		$bets = $this->Base_model->getDatas('tbl_bet', ['insert_at >=' => $filter->date_from, 'insert_at <=' => $filter->date_to]);

		$index = 0;
		foreach($bets as $bet)
		{
			if($this->FilterBet_model->filter($bet, $filter, $admins, $admins, $users) == false)continue;
			$index++;
			if($index < $filter->view_from)continue;

			if(isset($users[$bet->user_id]))
			{				
				$bet->user = $users[$bet->user_id]->name;
				$ag = $this->data_by_id($admins, $users[$bet->user_id]->parent_id);
				if($ag)
				{
					$bet->agency = $ag->login;
					$op = $this->data_by_id($admins, $ag->parent_id);
					if($op) $bet->operator = $op->login;
					else $bet->operator ='';
				}
				else
				{
					$bet->agency = '';
					$bet->operator = '';	
				}
			}
			else
			{
				$bet->user = '';
				$bet->agency = '';
				$bet->operator = '';
			}
			$res[]= $bet;
			if(count($res) >= $filter->view_count)break;				
		}

		$this->reply(200, 'ok', $res);		
	}

	public function get_bet_report()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$filter = $request->filter;
		$filter->date_from = date('Y-m-d H:i:s', strtotime($filter->date_from));
		$filter->date_to = date('Y-m-d H:i:s', strtotime($filter->date_to));

		$admins = $this->Base_model->getDatas('tbl_user', ['role <>' => 'user']);
		$loginUser = $this->data_by_id($admins, $userInfo->id);
		if($loginUser == null)
		{
			$this->reply(401, "permission is not allowed 0", null);
			return;
		}
		$usrs = $this->Base_model->getDatas('tbl_user', ['role' => 'user']);
		$users = [];
		foreach($usrs as $usr)$users[$usr->id] = $usr;

		$datetime = new DateTime($filter->date_from);
		$datetime_to = new DateTime($filter->date_to);

		$res = [];
		$days = [];
		$index = 0;
		while($datetime <= $datetime_to)
		{
			$index ++;
			if($index < $filter->view_from) 
			{
				$datetime->modify('+1 day');
				continue;
			}
				
			$days[] = $datetime->format('Y-m-d');
			$datetime->modify('+1 day');
			if(count($days)>=$filter->view_count) break;
		}

		$total_bets = 0;
		$total_amount = 0;
		$total_max_win = 0;
		$total_void = 0;
		$total_void_count = 0;
		$total_won = 0;
		$total_net = 0;

		foreach($days as $day)
		{
			$day_s = $day.' 00:00:00';
			$day_e = $day.' 23:59:59';
			$bets = $this->Base_model->getDatas('tbl_bet', ['insert_at >=' => $day_s, 'insert_at <=' => $day_e]);
			$t_amount = 0;
			$v_count = 0;
			$t_void = 0;
			$t_max_win = 0;
			$t_won = 0;
			$t_net = 0;
			foreach($bets as $bet)
			{
				if(!$this->FilterBet_model->filter($bet, $filter, $admins, $admins, $users)) continue;
				$t_amount += $bet->amount;
				if($bet->status ==2) {
					$v_count ++;
					$t_void += $bet->amount;
				}
				$t_max_win += $bet->max_win;
				$t_won += $bet->won_amount;
			}

			$total_amount += $t_amount;
			$total_max_win += $t_max_win;
			$total_won += $t_won;
			$total_void += $t_void;
			$total_net += $t_net;
			$total_bets += count($bets);
			$total_void_count += $v_count;

			$res[]=['date'=> $day, 'day'=>'', 'bets_count'=>count($bets), 
					'total_amount'=> number_format($t_amount, 2), 'total_max_win'=>number_format($t_max_win, 2),
					'void_count' => $v_count, 'total_void' => number_format($t_void, 2),
					'total_won' => number_format($t_won,2), 'total_net' => number_format($t_net, 2)];
		}
		$sumarry = [
			'total_bets_count' => $total_bets,
			'total_voids_count' => $total_void_count,
			'total_amount'=> $total_amount,
			'total_max_win'=> $total_max_win,
			'total_won'=> $total_won,
			'total_void'=> $total_void,
			'total_net'=> $total_net,
		];
		$this->reply(200, 'ok', ['days'=>$res, 'sumarry'=>$sumarry]);
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


	public function get_report_of_bettors()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$filter = $request->filter;
		$filter->date_from = date('Y-m-d H:i:s', strtotime($filter->date_from));
		$filter->date_to = date('Y-m-d H:i:s', strtotime($filter->date_to));

		$admins = $this->Base_model->getDatas('tbl_user', ['role <>' => 'user']);
		$loginUser = $this->data_by_id($admins, $userInfo->id);
		if($loginUser == null)
		{
			$this->reply(401, "permission is not allowed 0", null);
			return;
		}
		$usrs = $this->Base_model->getDatas('tbl_user', ['role' => 'user']);
		$users = [];
		foreach($usrs as $usr)$users[$usr->id] = $usr;


		$user_type = $filter->user_type;
		$res = [];

		$total_amount = 0;
		$total_max_win = 0;
		$total_void = 0;
		$total_won = 0;
		$total_net = 0;

		$operators  =  $this->Base_model->getDatas('tbl_admin', ['role'=>'operator']);
		$agencies  =  $this->Base_model->getDatas('tbl_admin', ['role'=>'agency']);

		$bets = $this->Base_model->getDatas('tbl_bet', ['insert_at >=' => $filter->date_from, 'insert_at <=' => $filter->date_to ]);
		foreach($bets as $bet)
		{
			if(!$this->FilterBet_model->filter($bet, $filter, $operators, $agencies, $users)) continue;

			$usr = null;
			$me =$users[$bet->user_id];
			if($me == null) continue;
			switch($user_type)
			{
				case 'operator':					
					$ag = $this->data_by_id($agencies, $me->parent_id);
					if($ag)
						$usr = $this->data_by_id($operators, $ag->parent_id);
				break;
				case 'agency':
					$usr = $this->data_by_id($agencies, $me->parent_id);
				break;
				case 'user':
					$usr = $me;
				break;
			}
			if($usr == null) continue;
			
			$ent = null;
			if(isset($res[$usr->id])) 
				$ent = $res[$usr->id];
			else
			{
				$ent = ['id'=>$usr->id, 'name'=> $usr->login, 'bets'=>0, 'amount'=>0, 'max_win'=>0, 'void'=>0, 'won'=>0, 'net'=>0];
			}

			$ent['bets'] = $ent['bets'] + 1;
			$ent['amount'] = $ent['amount'] + $bet->amount;
			$ent['max_win'] = $ent['max_win'] + $bet->max_win;			
			if($bet->status ==2)
				$ent['void'] = $ent['void'] + $bet->amount;
			$ent['won'] = $ent['won'] + $bet->won_amount;
			$ent['net'] = $ent['net'] + 0;
			$res[$usr->id] = $ent;

			$total_amount += $bet->amount;
			$total_max_win += $bet->max_win;
			if($bet->status ==2)
				$total_void += $bet->amount;
			$total_won += $bet->won_amount;
			$total_net += 0;
		}

		$res1 = [];
		foreach($res as $key=> $data) $res1[] = $data;

		$sumarry = [
			'total_users' => count($res),
			'total_amount'=> $total_amount,
			'total_max_win'=> $total_max_win,
			'total_won'=> $total_won,
			'total_void'=> $total_void,
			'total_net'=> $total_net
		];
		$this->reply(200, 'ok', ['result_type'=>$user_type, 'bettors'=>$res1, 'sumarry'=>$sumarry]);
	}


	public function void_bet()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$betid = $request->betid;
		$bet = $this->Base_model->getRow('tbl_bet', ['bet_id'=>$betid]);
		if($bet == null)
		{
			$this->reply(402, "invalid bet id", null);
			return;
		}

		if($bet->status == 2)
		{
			$this->reply(402, "already void bet", null);
			return;
		}

		$this->Base_model->updateData('tbl_bet', ['bet_id'=>$betid], ['status'=>2]);
		$this->reply(200, "ok", null);
		return;
	}

}
