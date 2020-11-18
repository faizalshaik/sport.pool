<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class UserTransactionApi extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->timeZone = 'Africa/Lagos';
		date_default_timezone_set($this->timeZone);
		$this->load->helper('url');
		$this->load->model('Base_model');
		$this->payStack_key = 'sk_test_822ed43cda7ed69158b51a5324357fe4eafdcdfb';
	}	



	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}	

	public function get_transactions()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;		
		if ($userInfo->role != "user")
			return $this->reply(401, 'Permission denied!', null);

		$filter = $request->filter;

		$from = date('Y-m-d H:i:s', strtotime($filter->date_from));
		$to = date('Y-m-d H:i:s', strtotime($filter->date_to));
		$cond = ['date >=' => $from, 'date <=' =>$to, 'user_id'=>$userInfo->id];
		if($filter->type !='all')
			$cond['type'] = $filter->type;

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
			if($row->type=='bet' || $row->type=='withdraw' || $row->type=='transfer')
				$row->given = $row->amount;
			else
				$row->received = $row->amount;

			if($index >= $filter->view_from && count($res) < $filter->view_count)
			{				
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

	public function start_deposite()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;		
		if ($userInfo->role != "user")
			return $this->reply(401, 'Permission denied!', null);

		$user = $this->Base_model->getRow('tbl_user', ['id'=>$userInfo->id]);
		if($user == null)
		{
			$this->reply(401, 'invalid token!', null);
			return;
		}
		$amount = $request->amount;

		if($amount <= 0)
		{
			$this->reply(401, 'invalid amount!', null);
			return;
		}
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize'); 
		curl_setopt($ch, CURLOPT_POST, 1);
		$authorization = "Authorization: Bearer ".$this->payStack_key;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$postdata = ['email'=> $user->email, 'amount'=>$amount * 100];
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
		$response = curl_exec($ch); 
		curl_close($ch); 

		$resdata = json_decode($response, false);
		if($resdata == null || !$resdata->status)
		{
			$this->reply(402, $resdata->message, null);
			return;
		}

		//update
		$this->Base_model->insertData('tbl_paystack', [
				'dt'=>date('Y-m-d H:i:s'), 
				'user_id'=>$user->id, 
				'amount'=>$amount,
				'reference' => $resdata->data->reference,
				'access_code' => $resdata->data->access_code
				]);
		$this->reply(200, $resdata->message, $resdata->data);
	}

	public function paystack_verify_call_back()
	{
		//$trxref = $this->input->get('trxref');
		$reference = $this->input->get('reference');
		if($reference=='')
			return;

		$paystack_trx = $this->Base_model->getRow('tbl_paystack', ['reference'=>$reference, 'status <>'=>'success']);
		if($paystack_trx == null)
			return;

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/'.$reference); 
		// curl_setopt($ch, CURLOPT_POST, 1);
		$authorization = "Authorization: Bearer ".$this->payStack_key;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$response = curl_exec($ch); 
		curl_close($ch); 
		$resData = json_decode($response);

		if($resData==null || $resData->status!=true)
		{
			echo "Can't verify your transaction";
			return;
		}

		if($resData->data->status == 'success')
		{
			$user = $this->Base_model->getRow('tbl_user', ['id'=>$paystack_trx->user_id]);
			if($user==null)
			{
				echo "Unknown user, please login and try again";
				return;	
			}
			$newBal = $user->balance + ($resData->data->amount/100); 

			//update transaction table
			$this->Base_model->insertData('tbl_transaction',
				['user_id' => $user->id, 'date'=>date('Y-m-d H:i:s'), 
				'type'=>'deposit', 'amount'=>($resData->data->amount/100),
				'descr' => 'TrxRef: '.$reference,
				'org_balance' => $user->balance,
				'new_balance'=>$newBal]);

			//update user table
			$this->Base_model->updateData('tbl_user', ['id'=>$user->id], ['balance'=>$newBal]);

			//update paystack table
			$this->Base_model->updateData('tbl_paystack', ['id'=>$paystack_trx->id], ['status'=>'success']);
			echo "Your payment is successed new balance is '.$newBal.'. Please click your balance to refresh";
			return;
		}
		else if($resData->data->status == 'failed')
		{
			$this->Base_model->updateData('tbl_paystack', ['id'=>$paystack_trx->id], ['status'=>'failed']);
			echo "Your payment was failed";
			return;
		}
		else if($resData->data->status == 'abandoned')
		{
			echo "Your payment is still in pending state";
			return;
		}
	}


	public function test()
	{
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize'); 
		curl_setopt($ch, CURLOPT_POST, 1);
		$authorization = "Authorization: Bearer ".$this->payStack_key;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$postdata = ['email'=> 'yusung0625@outlook.com', 'amount'=>200];
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
		$response = curl_exec($ch); 
		curl_close($ch); 

		echo $response;
	}

}