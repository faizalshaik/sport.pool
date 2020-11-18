<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class AdminData_api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Africa/Lagos');
		$this->load->model('User_model');
		$this->load->model('Base_model');
		$this->load->model('Week_model');		
		$this->load->model('FormatFixture_model');		
	}

	public function test()
	{
	}

	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}
	
	
	public function get_sports()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$this->reply(200, 'ok', $sports);
	}

	public function get_countries()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$sport_id = $request['sport'];

		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
		{
			return $this->reply(402, "invalid sport", null);
		}

		$tbl_country = 'tbl_'.$sport->key.'_country';
		$countries = $this->Base_model->getDatas($tbl_country, null);
		$this->reply(200, 'ok', $countries);
	}

	public function get_leagues()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$sport_id = $request['sport'];
		$country_id = $request['country'];

		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
		{
			return $this->reply(402, "invalid sport", null);
		}

		$tbl_league = 'tbl_'.$sport->key.'_league';
		$leagues = $this->Base_model->getDatas($tbl_league, ['country_key'=>$country_id]);
		$this->reply(200, 'ok', $leagues);
	}


	public function create_league()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$sport_id = $request['sport'];
		$country_id = $request['country'];
		$league = $request['league'];

		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
		{
			return $this->reply(402, "invalid sport", null);
		}
		$tbl_country = 'tbl_'.$sport->key.'_country';
		$country =$this->Base_model->getRow($tbl_country, ['key'=>$country_id]);
		if($country==null)
		{
			return $this->reply(402, "invalid country", null);
		}

		$tbl_league = 'tbl_'.$sport->key.'_league';
		$row = $this->Base_model->getRow($tbl_league, ['name'=>$league]);
		if($row != null)
		{
			return $this->reply(402, "already same league exist", null);
		}

		$max_key = $this->Base_model->get_max_value($tbl_league, 'key');
		if($max_key < 90000)
			$max_key = 90000;
		$new_key = $max_key + 1;

		$id = $this->Base_model->insertData($tbl_league, ['key'=>$new_key, 'name'=>$league, 'country_key'=>$country->key]);
		$this->reply(200, 'ok', ['id'=>$id, 'key'=>$new_key]);
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

	public function create_programs()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$sport_id = $request['sport'];
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
			return $this->reply(402, "invalid sport", null);

		$progs = $request['progs'];
		$optids = $request['opts'];
		$options = $this->Base_model->getDatas('tbl_option', null);
		$opts = [];
		foreach($optids as $optid)
		{
			$op = $this->data_by_id($options, $optid);
			if($op == null)
			{
				return $this->reply(402, "include invalid option id", null);
			}
			$opts[] = $op;
		}

		$tbl_fixture = 'tbl_'.$sport->key.'_fixture';
		$max_key = $this->Base_model->get_max_value($tbl_fixture, 'key');
		if($max_key < 9000000)
			$max_key = 9000000;
		$new_key = $max_key + 1;

		foreach($progs as $prog)
		{
			$prizes = [];
			for($i=0; $i<count($opts); $i++)
			{
				$opt = $opts[$i];
				$prizes[$opt->key] = $prog['prizes'][$i];
			}
			$dt = strtotime($prog['date_time']);			
			$week = $this->Week_model->get_by_date(date('Y-m-d', $dt));
			$this->Base_model->insertData($tbl_fixture, ['key'=>$new_key, 
														'qbet'=> $prog['qbet'],
														'year'=> $week['year'],
														'week'=> $week['week'],
														'date'=>date('Y-m-d', $dt),
														'time'=>date('H:i:s', $dt),
														'home_team' =>$prog['home_team'],
														'away_team' => $prog['away_team'],
														'league_key' => $prog['league_key'],
														'country_key' => $prog['country_key'],
														'odds' => json_encode($prizes)
														]);
			
			$new_key++;
		}
		return $this->reply(200, 'ok', null);
	}

	public function remove_custom_fixture()
	{
		$request = json_decode(file_get_contents('php://input'), true);		
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$sport_id = $request['sport'];
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
			return $this->reply(402, "invalid sport", null);

		$tbl_fixture = 'tbl_'.$sport->key.'_fixture';
		$row = $this->Base_model->getRow($tbl_fixture, ['date'=>$request['date'], 'qbet'=>$request['qbet']]);
		if($row==null)
		{
			return $this->reply(402, "invalid fixture", null);
		}
		$this->Base_model->deleteRow($tbl_fixture, ['date'=>$request['date'], 'qbet'=>$request['qbet']]);
		return $this->reply(200, "ok", null);
	}

	//for admin defined fixtures
	public function get_custom_fixtures()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$sport_id = $request['sport'];
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
			return $this->reply(402, "invalid sport", null);

		$tbl_country = 'tbl_'.$sport->key.'_country';
		$countries = $this->Base_model->getDatas($tbl_country, null);

		$tbl_league = 'tbl_'.$sport->key.'_league';
		$leagues = $this->Base_model->getDatas($tbl_league, null);

		$tbl_fixture = 'tbl_'.$sport->key.'_fixture';
		$cur_week = $this->Week_model->current();		
		$fixtures = $this->Base_model->getDatas($tbl_fixture, ['year' =>$cur_week['year'], 'week' =>$cur_week['week'], 'qbet <' => 100], 'qbet');

		$res = [];
		foreach($fixtures as $fixture)
		{			
			$fix = 	$this->FormatFixture_model->format($fixture);

			$lg = $this->data_by_key($leagues, $fixture->league_key);
			if($lg)	$fix['league'] = $lg->name;
			else $fix['league'] = '';

			$cntry = $this->data_by_key($countries, $fixture->country_key);
			if($cntry)	$fix['country'] = $cntry->name;
			else $fix['country'] = '';

			$fix['odds'] = $fixture->odds;
			$fix['visible'] = $fixture->visible;
			$res []= $fix;
		}
		$this->reply(200, 'ok', $res);
	}
		

	public function get_fixtures()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);


		$filter = $request['filter'];
		$date_from_dt = date('Y-m-d', strtotime($filter['date_from'])); 
		$date_from_time = date('H:i:s', strtotime($filter['date_from']));
		$date_to_dt = date('Y-m-d', strtotime($filter['date_to'])); 
		$date_to_time = date('H:i:s', strtotime($filter['date_to']));
		$finished = $filter['finished'];


		$sport_id = $filter['sport'];
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
			return $this->reply(402, "invalid sport", null);
		$tbl_fixture = 'tbl_'.$sport->key.'_fixture';

		$tbl_country = 'tbl_'.$sport->key.'_country';
		$countries = $this->Base_model->getDatas($tbl_country, null);

		$tbl_league = 'tbl_'.$sport->key.'_league';
		$leagues = $this->Base_model->getDatas($tbl_league, null);

		// $fixtures = $this->Base_model->getDatas($tbl_fixture, ['key >=' =>9000000, 'status <>' => 'Finished']);
		// $fixtures = $this->Base_model->getDatas($tbl_fixture, ['key >=' =>9000000]);
		$fixtures = $this->Base_model->getDatas($tbl_fixture, 
			['date >=' =>$date_from_dt, 'date <=' =>$date_to_dt], 'datetime');

		$res = [];		
		$from = $filter['from'];
		$count = $filter['count'];
		$index = 0;
		foreach($fixtures as $fixture)
		{			
			if($fixture->date == $date_from_dt && $fixture->time < $date_from_time) continue;
			if($fixture->date == $date_to_dt && $fixture->time > $date_to_time) continue;
			if(!$finished)
			{
				if($fixture->final_result != '' && $fixture->final_result != '-')
					continue;
			}
			$odds = json_decode($fixture->odds);
			if($odds == null)
				continue; 

			if($filter['qbet']> 0 && strstr(strval($fixture->qbet), strval($filter['qbet']))==null) continue;

			$index++;
			if($index < $from) 
				continue;
			$fix = 	$this->FormatFixture_model->format($fixture);

			$lg = $this->data_by_key($leagues, $fixture->league_key);
			if($lg)	$fix['league'] = $lg->name;
			else $fix['league'] = '';

			$cntry = $this->data_by_key($countries, $fixture->country_key);
			if($cntry)	$fix['country'] = $cntry->name;
			else $fix['country'] = '';

			$fix['odds'] = $fixture->odds;
			$fix['visible'] = $fixture->visible;
			$res []= $fix;
			if(count($res) >= $count)
				break;
		}
		$this->reply(200, 'ok', $res);
	}

	public function update_result()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);


		$sport_id =  $request['sport'];
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
			return $this->reply(402, "invalid sport", null);
		$tbl_fixture = 'tbl_'.$sport->key.'_fixture';

		$result = $request['result'];
		$ht =  $result['ht_result'][0].'-'.$result['ht_result'][1];
		$ft =  $result['ft_result'][0].'-'.$result['ft_result'][1];
		$this->Base_model->updateData($tbl_fixture, ['key'=>$result['key']], ['halftime_result'=>$ht, 'ft_result'=>$ft]);
		$this->reply(200, 'ok', null);
	}

	public function update_prize()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);


		$sport_id =  $request['sport'];
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
			return $this->reply(402, "invalid sport", null);
		$tbl_fixture = 'tbl_'.$sport->key.'_fixture';
		
		$prize = $request['prize'];
		$this->Base_model->updateData($tbl_fixture, ['qbet'=>$prize['qbet'], 'date'=>$prize['date']], ['odds'=>json_encode($prize['odds'])]);
		$this->reply(200, 'ok', json_encode($prize['odds']));
	}


	public function get_latest_result()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);
		if ($userInfo == null) return;
		if ($userInfo->role != "admin")
			return $this->reply(401, 'Permission denied!', null);

		$filter = $request['filter'];
		$sport_id =  $filter['sport'];
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
			return $this->reply(402, "invalid sport", null);


		$countries = $this->Base_model->getDatas('tbl_'.$sport->key.'_country', null);			
		$leagues = $this->Base_model->getDatas('tbl_'.$sport->key.'_league', null);
		// $datas = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', ['status' => 'Finished'], 'date', 'desc');
		$datas = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', ['status' => 'Finished'], ['date'=>'DESC', 'time'=>'DESC'], 'desc');

		//apply filter
		$from = $filter['from'];
		if($from < 1) $from = 1;
		$from--;
		$index = -1;

		$res = [];
		foreach($datas as $data)
		{
			if($filter['qbet'] > 0 && strstr(strval($data->key), strval($filter['qbet'])) == null) continue;			
			$index ++;			
			if($index < $from) continue;
			if(count($res) >= $filter['count']) break;

			$ev = $this->FormatFixture_model->format($data);
			$league = $this->data_by_key($leagues, $data->league_key);
			if($league)
				$ev['league'] = $league->name;
			else
				$ev['league'] = '';

			$country = $this->data_by_key($countries, $data->country_key);
			if($country)
				$ev['country'] = $country->name;
			else
				$ev['country'] = '';	
			
			$res[] = $ev;
		}		
		$this->reply(200, 'ok', $res);
	}	



}
