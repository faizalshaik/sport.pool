<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class AdminTransactionApi extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->timeZone = 'Africa/Lagos';
		date_default_timezone_set($this->timeZone);
		$this->load->helper('url');
		$this->load->model('Base_model');
	}	


	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}	

	public function get_transfer_list()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;		
		if ($userInfo->role != "admin" && $userInfo->role != "operator" && $userInfo->role != "agency")
			return $this->reply(401, 'Permission denied!', null);

		$filter = $request->filter;

		$from = date('Y-m-d H:i:s', strtotime($filter->date_from));
		$to = date('Y-m-d H:i:s', strtotime($filter->date_to));		
		$cond = ['date >=' => $from, 'date <=' =>$to];

		$user_id = 0;
		if($filter->user > 0)
			$user_id = $filter->user;
		else if($filter->agency > 0)
			$user_id = $filter->agency;
		else if(($userInfo->role=="admin" || $userInfo->role=="operator") && $filter->operator > 0)
			$user_id = $filter->operator;

		$users = [];
		$usrs = $this->Base_model->getDatas('tbl_user', null);
		foreach($usrs as $usr)
			$users[$usr->id] = $usr;

		// if($filter->type !='all')
		// 	$cond['type'] = $filter->type;		
		$res = [];
		$rows = $this->Base_model->getDatas('tbl_transaction', $cond);

		$tt_given = 0;
		$tt_received = 0;

		$t_given = 0;
		$t_received = 0;

		$index = 0;
		foreach($rows as $row)
		{
			$index++;
			$row->given = 0;
			$row->received = 0;
			if($row->user_id==$row->payer_id)
				$row->given = $row->amount;
			else
				$row->received = $row->amount;

			if($index >= $filter->view_from && count($res) < $filter->view_count)
			{				
				$user = $users[$row->user_id];
				$payer = $users[$row->payer_id];
				$receiver = $users[$row->receiver_id];
				if($user)
					$row->user = $user->login;
				else
					$row->user = '';
				if($payer)
					$row->payer = $payer->login;
				else
					$row->payer = '';
				if($receiver)
					$row->receiver = $receiver->login;
				else
					$row->receiver = '';

				$res[] = $row;
				$t_given += $row->given;
				$t_received += $row->received;
			}
			$tt_given += $row->given;
			$tt_received += $row->received;
		}
		$this->reply(200, 'ok', [
				'transactions'=>$res, 
				'summary'=>['given'=>$t_given, 'received'=>$t_received],
				'summary_date'=>['given'=>$tt_given, 'received'=>$tt_received]]);
	}

	public function transfer()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;		
		if ($userInfo->role != "admin" && $userInfo->role !='operator' && $userInfo->role !='agency')
			return $this->reply(401, 'Permission denied!', null);

		$user_id = $request->user_id;
		$amount = $request->amount;
		$move_type = $request->move_type;

		if($move_type !='Deposit' && $move_type !='Withdraw')
		{
			$this->reply(401, 'invalid movement type!', null);
			return;
		}			

		if($amount <= 0)
		{
			$this->reply(401, 'invalid amount!', null);
			return;
		}

		$me = $this->Base_model->getRow('tbl_user', ['id'=>$userInfo->id]);
		$user = $this->Base_model->getRow('tbl_user', ['id'=>$user_id]);
		if($me ==null || $user==null)
		{
			return $this->reply(402, 'Invalid token or user!', null);
		}

		//check balance
		if($move_type=='Deposit' && $me->balance < $amount)
			return $this->reply(402, 'Insufficient balance!', null);
		else if($move_type=='Withdraw' && $user->balance < $amount)
			return $this->reply(402, "Insufficient user's balance!", null);

		//update balances and transactions
		$new_bal = 0;
		$new_bal1 = 0;
		if($move_type == 'Deposit')
		{
			$new_bal = $me->balance - $amount;
			$new_bal1 = $user->balance + $amount;	
		}
		else
		{
			$new_bal = $me->balance + $amount;
			$new_bal1 = $user->balance - $amount;	
		}
		$this->Base_model->updateData('tbl_user', ['id'=>$me->id], ['balance'=>$new_bal]);
		$this->Base_model->updateData('tbl_user', ['id'=>$user->id], ['balance'=>$new_bal1]);

		if($move_type == 'Deposit')
		{
			$this->Base_model->insertData('tbl_transaction',
			['user_id' => $me->id, 
			 'payer_id' => $me->id, 
			 'receiver_id' => $user->id, 
			 'date'=>date('Y-m-d H:i:s'), 
			 'type'=>'transfer', 
			 'amount'=>$amount,
			 'descr' => 'transfer to user',
			 'org_balance' => $me->balance,
			 'new_balance'=>$new_bal]);

			 $this->Base_model->insertData('tbl_transaction',
			 ['user_id' => $user->id, 
			  'payer_id' => $me->id, 
			  'receiver_id' => $user->id, 
			  'date'=>date('Y-m-d H:i:s'), 
			  'type'=>'receive', 
			  'amount'=>$amount,
			  'descr' => $me->role.' fund',
			  'org_balance' => $user->balance,
			  'new_balance'=>$new_bal1]); 
		}
		else
		{
			$this->Base_model->insertData('tbl_transaction',
			['user_id' => $me->id, 
			 'payer_id' => $user->id, 
			 'receiver_id' => $me->id, 
			 'date'=>date('Y-m-d H:i:s'), 
			 'type'=>'deposit', 
			 'amount'=>$amount,
			 'descr' => 'withdraw from user',
			 'org_balance' => $me->balance,
			 'new_balance'=>$new_bal]);

			 $this->Base_model->insertData('tbl_transaction',
			 ['user_id' => $user->id, 
			  'payer_id' => $user->id, 
			  'receiver_id' => $me->id, 
			  'date'=>date('Y-m-d H:i:s'), 
			  'type'=>'withdraw', 
			  'amount'=>$amount,
			  'descr' => $me->role.' withdraw',
			  'org_balance' => $user->balance,
			  'new_balance'=>$new_bal1]);
		}
		$this->reply(200, 'ok', ['me_balance'=>$new_bal, 'user_balance'=>$new_bal1]);
	}

}