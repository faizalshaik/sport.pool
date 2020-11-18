<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class Admin_api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Africa/Lagos');
		$this->load->model('User_model');
		$this->load->model('Base_model');
		$this->load->model('FormatFixture_model');		

		$this->load->model('Week_model');
	}

	public function get_current_week()
	{
		$this->reply(200, 'ok', $this->Week_model->current());
	}

	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}	

	public function get_options()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$options = $this->Base_model->getDatas('tbl_option', null);
		$this->reply(200, 'ok', $options);
	}


	public function update_option()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$option = $request['option'];
		$row = $this->Base_model->getRow('tbl_option', ['name' => $option['name']]);
		if($row!=null && $row->id != $option['id'])
		{
			return $this->reply(401, 'Already same name exist', null);
		}

		$row = $this->Base_model->getRow('tbl_option', ['key' => $option['key']]);
		if($row!=null && $row->id != $option['id'])
		{
			return $this->reply(401, 'Already same key exist', null);
		}
		$this->Base_model->updateData('tbl_option', ['id'=>$option['id']], ['name'=>$option['name'], 'key'=>$option['key'], 'relation'=> $option['relation']]);
		$this->reply(200, 'ok', null);
	}

	public function add_option()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$option = $request['option'];
		$row = $this->Base_model->getRow('tbl_option', ['name' => $option['name']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same name exist', null);
		}

		$row = $this->Base_model->getRow('tbl_option', ['key' => $option['key']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same key exist', null);
		}
		$id = $this->Base_model->insertData('tbl_option',['name'=>$option['name'], 'key'=>$option['key'], 'relation'=>$option['relation']]);
		$this->reply(200, 'ok', $id);
	}	

	public function remove_option()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$id = $request['id'];
		$id = $this->Base_model->deleteRow('tbl_option',['id'=>$id]);
		$this->reply(200, 'ok', $id);
	}	

	public function get_groups()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$groups = $this->Base_model->getDatas('tbl_group', null);
		$this->reply(200, 'ok', $groups);
	}


	public function update_group()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$group = $request['group'];
		$row = $this->Base_model->getRow('tbl_group', ['name' => $group['name']]);
		if($row!=null && $row->id != $group['id'])
		{
			return $this->reply(401, 'Already same name exist', null);
		}
		$this->Base_model->updateData('tbl_group', ['id'=>$group['id']], ['name'=>$group['name'], 'descr'=>$group['descr']]);
		$this->reply(200, 'ok', null);
	}

	public function add_group()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$group = $request['group'];
		$row = $this->Base_model->getRow('tbl_group', ['name' => $group['name']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same name exist', null);
		}
		$id = $this->Base_model->insertData('tbl_group',['name'=>$group['name'], 'descr'=>$group['descr']]);
		$this->reply(200, 'ok', $id);
	}	

	public function remove_group()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$id = $request['id'];
		$id = $this->Base_model->deleteRow('tbl_group',['id'=>$id]);
		$this->reply(200, 'ok', $id);
	}	


	public function get_group_options()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$options = $this->Base_model->getDatas('tbl_option', null);
		$groups = $this->Base_model->getDatas('tbl_group', null);
		$group_options = $this->Base_model->getDatas('tbl_option_group', null, 'group_id');
		$this->reply(200, 'ok', ['group_options' => $group_options, 'groups'=>$groups, 'options'=>$options]);
	}


	public function update_group_option()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$group_option = $request['group_option'];
		$row = $this->Base_model->getRow('tbl_option_group', ['group_id' => $group_option['group_id'], 'option_id' => $group_option['option_id']]);
		if($row!=null && $row->id != $group_option['id'])
		{
			return $this->reply(401, 'Already same entry exist', null);
		}
		$this->Base_model->updateData('tbl_option_group', ['id'=>$group_option['id']], ['group_id'=>$group_option['group_id'], 'option_id'=>$group_option['option_id']]);
		$this->reply(200, 'ok', null);
	}

	public function add_group_option()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$group_option = $request['group_option'];
		$row = $this->Base_model->getRow('tbl_option_group', ['group_id' => $group_option['group_id'], 'option_id' => $group_option['option_id']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same entry exist', null);
		}
		$id = $this->Base_model->insertData('tbl_option_group',['group_id'=>$group_option['group_id'], 'option_id'=>$group_option['option_id']]);
		$this->reply(200, 'ok', $id);
	}	

	public function remove_group_option()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$id = $request['id'];
		$id = $this->Base_model->deleteRow('tbl_option_group',['id'=>$id]);
		$this->reply(200, 'ok', $id);
	}	
	

	
	public function get_results()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$options = $this->Base_model->getDatas('tbl_result', null);
		$this->reply(200, 'ok', $options);
	}


	public function update_result()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$result = $request['result'];
		$row = $this->Base_model->getRow('tbl_result', ['name' => $result['name']]);
		if($row!=null && $row->id != $result['id'])
		{
			return $this->reply(401, 'Already same name exist', null);
		}

		$id = $result['id'];
		unset($result['id']);
		$this->Base_model->updateData('tbl_result', ['id'=>$id], $result);
		$this->reply(200, 'ok', null);
	}

	public function add_result()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$result = $request['result'];
		$row = $this->Base_model->getRow('tbl_result', ['name' => $result['name']]);
		if($row!=null)
		{
			return $this->reply(401, 'Already same name exist', null);
		}

		$id = $this->Base_model->insertData('tbl_result',$result);
		$this->reply(200, 'ok', $id);
	}	

	public function remove_result()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$id = $request['id'];
		$id = $this->Base_model->deleteRow('tbl_result',['id'=>$id]);
		$this->reply(200, 'ok', $id);
	}	


	public function get_option_results()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$results = $this->Base_model->getDatas('tbl_result', null);
		$options = $this->Base_model->getDatas('tbl_option', null);		
		$option_results = $this->Base_model->getDatas('tbl_option_result', null);
		$vals = [];
		foreach($option_results as $opt_rslt)
		{
			$vals[$opt_rslt->option_id.'_'.$opt_rslt->result_id] = $opt_rslt->state;
		}

		$opt_rsts = [];
		foreach($options as $opt)
		{
			$rsult_vals = [];
			foreach($results as $rslt)
			{
				// find val
				$val = 'Bad';
				if(isset($vals[$opt->id.'_'.$rslt->id])) 
					$val = $vals[$opt->id.'_'.$rslt->id];
				$rsult_vals[] = $val;
			}

			$opt->vals = $rsult_vals;
			$opt_rsts[]= $opt;
		}
		$this->reply(200, 'ok', ['results'=>$results, 'options'=>$opt_rsts]);
	}

	public function update_option_result()
	{
		$request = json_decode(file_get_contents('php://input'), true);

		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$option_id = $request['option_id'];
		$result_id = $request['result_id'];
		$state = $request['state'];
		$row = $this->Base_model->getRow('tbl_option_result', ['option_id'=>$option_id, 'result_id'=>$result_id]);
		if($row)
		{
			$this->Base_model->updateData('tbl_option_result', ['option_id'=>$option_id, 'result_id'=>$result_id], ['state'=>$request['state']]);
		}
		else
		{
			$this->Base_model->insertData('tbl_option_result', ['option_id'=>$option_id, 'result_id'=>$result_id, 'state'=>$state]);
		}
		$this->reply(200, 'ok', null);
	}


	private function get_data_by_key($lst, $key)
	{
		foreach($lst as $itm)
		{
			if($itm->key == $key) return $itm;
		}
		return null;
	}	

	
	
}
