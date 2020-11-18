<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class AdminUser_api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Africa/Lagos');
		$this->load->model('User_model');
		$this->load->model('Base_model');
	}

	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}

	protected function get_data_by_id($lst, $id)
	{
		foreach($lst as $item)
		{
			if($item->id == $id)
				return $item;
		}
		return null;
	}


	public function get_operators()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$users = $this->Base_model->getDatas('tbl_user', ['role <>' =>'user']);
		$res = [];
		foreach($users as $usr)
		{
			if($usr->role != 'operator') continue;
			if($userInfo->role =='operator' && $userInfo->id != $usr->id) continue;
			if($userInfo->role =='agency' && $userInfo->parent_id != $usr->id) continue;

			$usr->password = '';
			unset($usr->logined_at);
			unset($usr->token);
			unset($usr->register_confirmed);
			unset($usr->last_bet_id);
			unset($usr->last_bet_time);
			unset($usr->profile_img);
			$res[]= $usr;
		}
		$this->reply(200, 'ok', $res);
	}


	public function update_operator()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$operator = $request['user'];
		$row = $this->Base_model->getRow('tbl_user', ['login' => $operator['login']]);
		if($row!=null && $row->id != $operator['id'])
		{
			return $this->reply(401, 'Already same login exist', null);
		}

		$row = $this->Base_model->getRow('tbl_user', ['email' => $operator['email']]);
		if($row!=null && $row->id != $operator['id'])
		{
			return $this->reply(401, 'Already same email exist', null);
		}

		$id = $operator['id'];
		$operator['parent_id'] = 0;
		unset($operator['id']);
		unset($operator['role']);
		if($operator['password'] != '')
			$operator['password'] = password_hash($operator['password'], PASSWORD_DEFAULT);
		else
			unset($operator['password']);			

		$this->Base_model->updateData('tbl_user', ['id'=>$id], $operator);
		$this->reply(200, 'ok', null);
	}

	public function add_operator()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$operator = $request['user'];
		$row = $this->Base_model->getRow('tbl_user', ['login' => $operator['login']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same login exist', null);
		}

		$row = $this->Base_model->getRow('tbl_user', ['email' => $operator['email']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same email exist', null);
		}

		$operator['role'] = 'operator';
		$operator['created_at'] = date('Y-m-d');
		$operator['parent_id'] = $userInfo->id;
		$operator['password'] = password_hash($operator['password'], PASSWORD_DEFAULT);
		$id = $this->Base_model->insertData('tbl_user', $operator);
		$this->reply(200, 'ok', $id);
	}	

	public function remove_operator()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;


		if($userInfo->role !='admin')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$id = $request['id'];
		$id = $this->Base_model->deleteRow('tbl_user',['id'=>$id]);
		$this->reply(200, 'ok', $id);
	}


	public function get_agencis()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$operator_id = $request['operator'];
		$users = [];
		if($operator_id == 0)
			$users = $this->Base_model->getDatas('tbl_user', ['role <>' =>'user']);
		else
			$users = $this->Base_model->getDatas('tbl_user', ['parent_id'=>$operator_id]);
		$res = [];
		foreach($users as $usr)
		{
			if($usr->role != 'agency') continue;
			if($userInfo->role =='operator' && $userInfo->id != $usr->parent_id) continue;
			if($userInfo->role =='agency' && $userInfo->id != $usr->id) continue;

			$usr->password = '';
			unset($usr->logined_at);
			unset($usr->token);
			unset($usr->register_confirmed);
			unset($usr->last_bet_id);
			unset($usr->last_bet_time);
			unset($usr->profile_img);

			$res[]= $usr;
		}
		$this->reply(200, 'ok', $res);
	}


	public function update_agency()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$agency = $request['user'];
		$id = $agency['id'];
		if($userInfo->role =='operator')
		{
			$row = $this->Base_model->getRow('tbl_user', ['id' => $id]);
			if($row->parent_id != $userInfo->id)
			{
				$this->reply(401, "permission is not allowed !!!", null);
				return;	
			}
		}


		$row = $this->Base_model->getRow('tbl_user', ['login' => $agency['login']]);
		if($row!=null && $row->id != $agency['id'])
		{
			return $this->reply(401, 'Already same login exist', null);
		}

		$row = $this->Base_model->getRow('tbl_user', ['email' => $agency['email']]);
		if($row!=null && $row->id != $agency['id'])
		{
			return $this->reply(401, 'Already same email exist', null);
		}
		
		unset($agency['id']);
		unset($agency['role']);		
		if($agency['password'] != '')
			$agency['password'] = password_hash($agency['password'], PASSWORD_DEFAULT);
		else
			unset($agency['password']);

		$this->Base_model->updateData('tbl_user', ['id'=>$id], $agency);
		$this->reply(200, 'ok', null);
	}


	public function add_agency()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$agency = $request['user'];
		if($userInfo->role =='operator' && $userInfo->id != $agency['parent_id'])
		{
			$this->reply(401, "permission is not allowed !!!", null);
			return;
		}

		
		$row = $this->Base_model->getRow('tbl_user', ['login' => $agency['login']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same login exist', null);
		}

		$row = $this->Base_model->getRow('tbl_user', ['email' => $agency['email']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same email exist', null);
		}

		$agency['role'] = 'agency';
		$agency['created_at'] = date('Y-m-d');
		$agency['password'] = password_hash($agency['password'], PASSWORD_DEFAULT);

		$id = $this->Base_model->insertData('tbl_user', $agency);
		$this->reply(200, 'ok', $id);
	}	

	public function remove_agency()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$id = $request['id'];
		if($userInfo->role =='operator')
		{
			$row = $this->Base_model->getRow('tbl_user', ['id' => $id]);
			if($row->parent_id != $userInfo->id)
			{
				$this->reply(401, "permission is not allowed !!!", null);
				return;	
			}
		}

		$id = $this->Base_model->deleteRow('tbl_user',['id'=>$id]);
		$this->reply(200, 'ok', $id);
	}


	public function get_users()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency' )
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$parents = [];
		if($userInfo->role =='agency') 
			$parents[$userInfo->id] = 1;
		else if($userInfo->role =='operator')
		{
			if($request['agency'] > 0)
			{
				$parents[$request['agency']] = 1;
			}
			else
			{
				$ags = $this->Base_model->getDatas('tbl_user', ['parent_id'=>$userInfo->id]);
				foreach($ags as $ag) 
					$parents[$ag->id] = 1;
			}
		}

		$users = $this->Base_model->getDatas('tbl_user', ['role'=> 'user']);
		$res = [];
		foreach($users as $usr)
		{
			if(count($parents) > 0 && !isset($parents[$usr->parent_id])) 
				continue;

			$usr->password = '';
			unset($usr->logined_at);
			unset($usr->token);
			unset($usr->register_confirmed);
			unset($usr->last_bet_id);
			unset($usr->last_bet_time);
			unset($usr->profile_img);

			$res[]= $usr;
		}
		$this->reply(200, 'ok', $res);
	}


	public function update_user()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency' )
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$agency = $request['user'];
		$id = $agency['id'];
		if($userInfo->role =='agency')
		{
			$row = $this->Base_model->getRow('tbl_user', ['id' => $agency['id']]);
			if($row == null || $row->id != $userInfo->parent_id)
			{
				$this->reply(401, "permission is not allowed !!!", null);
				return;	
			}
		}
		else if($userInfo->role =='operator')
		{
			$row = $this->Base_model->getRow('tbl_user', ['id' => $agency['id']]);
			if($row == null)
			{
				$this->reply(401, "permission is not allowed !!!", null);
				return;	
			}
			$row = $this->Base_model->getRow('tbl_user', ['id' => $row->parent_id]);
			if($row == null || $row->parent_id != $userInfo->id)
			{
				$this->reply(401, "permission is not allowed !!! !!!", null);
				return;	
			}
		}



		$agency = $request['user'];
		$row = $this->Base_model->getRow('tbl_user', ['login' => $agency['login']]);
		if($row!=null && $row->id != $agency['id'])
		{
			return $this->reply(401, 'Already same login exist', null);
		}

		$row = $this->Base_model->getRow('tbl_user', ['email' => $agency['email']]);
		if($row!=null && $row->id != $agency['id'])
		{
			return $this->reply(401, 'Already same email exist', null);
		}

		$id = $agency['id'];
		unset($agency['id']);
		if($agency['password'] != '')
			$agency['password'] = password_hash($agency['password'], PASSWORD_DEFAULT);
		else
			unset($agency['password']);		

		$this->Base_model->updateData('tbl_user', ['id'=>$id], $agency);
		$this->reply(200, 'ok', null);
	}


	public function add_user()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency' )
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$agency = $request['user'];
		$id = $agency['id'];
		if($userInfo->role =='agency' && $agency['parent_id']!=$userInfo->id)
		{
			$this->reply(401, "permission is not allowed !!!", null);
			return;	
		}
		else if($userInfo->role =='operator')
		{
			$row = $this->Base_model->getRow('tbl_user', ['id' => $agency['parent_id']]);
			if($row == null || $row->parent_id != $userInfo->id)
			{
				$this->reply(401, "permission is not allowed !!! !!!", null);
				return;	
			}
		}

		$row = $this->Base_model->getRow('tbl_user', ['login' => $agency['login']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same login exist', null);
		}

		$row = $this->Base_model->getRow('tbl_user', ['email' => $agency['email']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same email exist', null);
		}

		$agency['role'] = 'user';
		$agency['created_at'] = date('Y-m-d');
		$agency['password'] = password_hash($agency['password'], PASSWORD_DEFAULT);		
		$id = $this->Base_model->insertData('tbl_user', $agency);
		$this->reply(200, 'ok', $id);
	}	

	public function remove_user()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency' )
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$id = $request['id'];
		if($userInfo->role =='agency')
		{
			$row = $this->Base_model->getRow('tbl_user', ['id' => $id]);
			if($row == null || $row->id != $userInfo->parent_id)
			{
				$this->reply(401, "permission is not allowed !!!", null);
				return;	
			}
		}
		else if($userInfo->role =='operator')
		{
			$row = $this->Base_model->getRow('tbl_user', ['id' => $id]);
			if($row == null)
			{
				$this->reply(401, "permission is not allowed !!!", null);
				return;	
			}
			$row = $this->Base_model->getRow('tbl_user', ['id' => $row->parent_id]);
			if($row == null || $row->parent_id != $userInfo->id)
			{
				$this->reply(401, "permission is not allowed !!! !!!", null);
				return;	
			}
		}

		$id = $this->Base_model->deleteRow('tbl_user',['id'=>$id]);
		$this->reply(200, 'ok', $id);
	}

	public function get_all_users()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;

		if($userInfo->role !='admin' && $userInfo->role !='operator' && $userInfo->role !='agency' )
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}

		$filter = $request->filter;
		$parents = [];
		if($filter->agency > 0)
			$parents[$filter->agency] = 1;
		else if($filter->operator > 0)
		{
			$ags = $this->Base_model->getDatas('tbl_user', ['parent_id'=>$userInfo->id]);
			foreach($ags as $ag) 
				$parents[$ag->id] = 1;

		}
		else if($userInfo->role =='admin')
		{
			$ags = $this->Base_model->getDatas('tbl_user', ['role'=>'agency']);
			foreach($ags as $ag) 
				$parents[$ag->id] = 1;
		}
		else if($userInfo->role =='operator')
		{
			$ags = $this->Base_model->getDatas('tbl_user', ['parent_id'=>$userInfo->id]);
			foreach($ags as $ag) 
				$parents[$ag->id] = 1;
		}
		else
			$parents[$userInfo->id] = 1;



		$users = $this->Base_model->getDatas('tbl_user', ['role'=> 'user']);
		$res = [];
		$index = 0;		
		foreach($users as $usr)
		{
			if(count($parents) > 0 && !isset($parents[$usr->parent_id])) 
				continue;
			$index ++;
			if($index < $filter->view_from) continue;
			$usr->password = '';
			unset($usr->logined_at);
			unset($usr->token);
			unset($usr->register_confirmed);
			unset($usr->last_bet_id);
			unset($usr->last_bet_time);
			unset($usr->profile_img);
			$res[]= $usr;
			if(count($res) >= $filter->view_count)
				break;
		}
		$this->reply(200, 'ok', $res);
	}


}
