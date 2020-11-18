<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class AdminMaintain_api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Africa/Lagos');
		$this->load->model('User_model');
		$this->load->model('Base_model');
		$this->load->model('Week_model');		
	}
	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}

	public function get_fixtures_count()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$res = [];
		foreach($sports as $sport)
		{
			$tbl_fixture = 'tbl_'.$sport->key.'_fixture';
			$counts = $this->Base_model->getCounts($tbl_fixture, null);
			$oldest = $this->Base_model->get_min_value($tbl_fixture, 'date');
			$latest = $this->Base_model->get_max_value($tbl_fixture, 'date');
			$res[] = ['id' => $sport->id, 'name' => $sport->name, 'value' => $counts, 'oldest'=>$oldest, 'latest'=>$latest];
		}
		$this->reply(200, 'ok', $res);
	}

	public function clear_fixtures()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$sport_id = $request['sport'];
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
		{
			return $this->reply(402, "invalid sport", null);
		}	
		$tbl_fixture = 'tbl_'.$sport->key.'_fixture';
		$dt = date('Y-m-d', strtotime($request['date']));
		$this->Base_model->deleteRow($tbl_fixture, ['date <' => $dt]);
		$this->reply(200, 'ok', null);
	}	

	public function get_bets_count()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$counts = $this->Base_model->getCounts('tbl_bet', null);
		$oldest = $this->Base_model->get_min_value('tbl_bet', 'insert_at');
		$latest = $this->Base_model->get_max_value('tbl_bet', 'insert_at');
		$res = ['value' => $counts, 'oldest'=>$oldest, 'latest'=>$latest];
		$this->reply(200, 'ok', $res);
	}

	public function clear_bets()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$dt = date('Y-m-d', strtotime($request['date']));
		$this->Base_model->deleteRow('tbl_bet', ['insert_at <' => $dt]);
		$this->reply(200, 'ok', null);
	}

	public function weeks_for_display()
	{
		$cur_week = $this->Week_model->current();
		$cur_wk = $this->Base_model->getRow('tbl_week', ['from >=' => $cur_week['from'], 'to <=' => $cur_week['to']]);
		if($cur_wk==null)
		{
			$id = $this->Base_model->insertData('tbl_week', $cur_week);
			$cur_wk = $cur_week;
			$cur_wk['id'] = $id;
			$cur_wk = json_decode(json_encode($cur_wk), false);
		}

		$datetime = new DateTime(date('Y-m-d'));
		$datetime->modify('-7 day');
		$prev_week = $this->Week_model->get_by_date($datetime->format('Y-m-d'));
		$prev_wk = $this->Base_model->getRow('tbl_week', ['from >=' => $prev_week['from'], 'to <=' => $prev_week['to']]);
		if($prev_wk==null)
		{	
			$prev_week['week'] = $cur_wk->week - 1;
			$id = $this->Base_model->insertData('tbl_week', $prev_week);
			$prev_wk = $prev_week;
			$prev_wk['id'] = $id;
			$prev_wk = json_decode(json_encode($prev_wk), false);
		}

		$datetime->modify('+14 day');
		$next_week = $this->Week_model->get_by_date($datetime->format('Y-m-d'));
		$next_wk = $this->Base_model->getRow('tbl_week', ['from >=' => $next_week['from'], 'to <=' => $next_week['to']]);
		if($next_wk==null)
		{
			$next_week['week'] = $cur_wk->week + 1;			
			$id = $this->Base_model->insertData('tbl_week', $next_week);
			$next_wk = $next_week;
			$next_wk['id'] = $id;
			$next_wk = json_decode(json_encode($next_wk), false);
		}

		$datetime->modify('+7 day');
		$next_week1 = $this->Week_model->get_by_date($datetime->format('Y-m-d'));
		$next_wk1 = $this->Base_model->getRow('tbl_week', ['from >=' => $next_week1['from'], 'to <=' => $next_week1['to']]);
		if($next_wk1==null)
		{
			$next_week1['week'] = $next_wk->week + 1;
			$id = $this->Base_model->insertData('tbl_week', $next_week1);
			$next_wk1 = $next_week1;
			$next_wk1['id'] = $id;
			$next_wk1 = json_decode(json_encode($next_wk1), false);
		}
		$this->reply(200, 'ok', ['prev'=>$prev_wk, 'cur'=>$cur_wk, 'next'=>$next_wk, 'next1'=>$next_wk1]);
	}	

	public function save_week()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$week = $request['week'];
		$id = $week['id'];
		unset($week['id']);
		unset($week['from']);
		unset($week['to']);
		$this->Base_model->updateData('tbl_week', ['id'=>$id], $week);
		$this->reply(200, 'ok', null);
	}

	public function settings()
	{
		$datas = $this->Base_model->getDatas('tbl_setting', null);
		$res = [];
		foreach($datas as $row)
		{
			$res[$row->name] = $row->value;
		}
		$this->reply(200, 'ok', $res);
	}	

	public function save_settings()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$setting = $request['setting'];
		foreach($setting as $name=>$value)
		{
			$this->Base_model->updateData('tbl_setting', ['name'=>$name], ['value'=>$value]);
		}
		$this->reply(200, 'ok', null);
	}		

	public function odd_limits()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$groups = $this->Base_model->getDatas('tbl_group', null, 'id');
		$this->reply(200, 'ok', $groups);
	}
	public function odd_limit_update()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$id = $request['id'];
		$max_odd = $request['max_odd'];
		$odd_increse = $request['odd_increase'];
		$this->Base_model->updateData('tbl_group', ['id'=>$id], ['max_odd'=>$max_odd, 'odd_increase' =>$odd_increse]);
		$this->reply(200, 'ok', null);
	}	

	public function commissions()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$cmms = $this->Base_model->getDatas('tbl_commission', null, 'under');
		$this->reply(200, 'ok', $cmms);
	}
	public function commission_update()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$id = $request['id'];
		$max_stake = $request['max_stake'];
		$commission = $request['commission'];
		$status = $request['status'];
		$this->Base_model->updateData('tbl_commission', ['id'=>$id], 
			['max_stake'=>$max_stake, 'commission' =>$commission, 'status'=>$status]);
		$this->reply(200, 'ok', null);
	}



	public function bet_bonus()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$bonuses = $this->Base_model->getDatas('tbl_bonus', null, 'events');
		$this->reply(200, 'ok', $bonuses);
	}

	public function add_bet_bonus()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);
		$bonus = $request['bonus'] ;

		$row = $this->Base_model->getRow('tbl_bonus', ['events'=>$bonus['events']]);
		if($row!=null)
		{
			$this->reply(402, 'Already exist!', null);
			return;
		}

		$id = $this->Base_model->insertData('tbl_bonus', $bonus);
		$this->reply(200, 'ok', $id);
	}

	public function update_bet_bonus()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);
		$bonus = $request['bonus'] ;

		$row = $this->Base_model->getRow('tbl_bonus', ['events'=>$bonus['events']]);
		if($row!=null && $row->id != $bonus['id'])
		{
			$this->reply(402, 'Already exist!', null);
			return;
		}
		$id = $bonus['id'];
		unset($bonus['id']);
		$id = $this->Base_model->updateData('tbl_bonus', ['id'=>$id], $bonus);

		$this->reply(200, 'ok', null);
	}

	public function delete_bet_bonus()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);
		$id = $request['id'] ;
		$this->Base_model->deleteRow('tbl_bonus', ['id'=>$id]);
		$this->reply(200, 'ok', null);
	}





	

}
