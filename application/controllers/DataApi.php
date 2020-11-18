<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class DataApi extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->timeZone = 'Africa/Lagos';
		date_default_timezone_set($this->timeZone);

		$this->load->helper('url');
		$this->load->model('Base_model');
		$this->load->model('Week_model');
		$this->load->model('Setting_model');
		$this->load->model('FormatFixture_model');
		$this->load->model('OptionGroup_model');				

		$this->tblCountry = 'tbl_country';
		$this->tblLeague = 'tbl_league';
		$this->tblFootballFixture = 'tbl_football_fixture';
	}
	// public function test()
	// {
	// 	echo date('Y-m-d');
	// 	echo json_encode($this->Week_model->get_by_date('2020-09-27'));
	// }
	
	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}	

	private function get_table_names($kind)
	{
		$tbls = [];
		$tbls['country'] = 'tbl_'.$kind.'_country';
		$tbls['league'] = 'tbl_'.$kind.'_league';
		$tbls['fixture'] = 'tbl_'.$kind.'_fixture';
		$tbls['odd'] = 'tbl_'.$kind.'_odd';
		return $tbls;
	}

	private function format_event($event)
	{
		$results = explode('-', $event->final_result);
		if(count($results)==2)
		{
			$event->final_result = $results;
		}
		else
		{
			$event->final_result = ['-', '-'];
		}
		return $event;
	}


	private function find_country($countries, $key)
	{
		foreach($countries as $country)
		{
			if($country->key == $key) 
				return $country;
		}
		return null;
	}
	private function find_league($leagues, $key)
	{
		foreach($leagues as $league)
		{
			if($league->key == $key) 
				return $league;
		}
		return null;
	}
	private function find_data($list, $id)
	{
		foreach($list as $ele)
		{
			if($ele->id == $id) return $ele;
		}
		return null;
	}


	public function get_base_infos()
	{
		$request = json_decode(file_get_contents('php://input'), false);		
		$localtime = $request->localtime;

		$options = $this->Base_model->getDatas('tbl_option', null);
		$option_groups = $this->OptionGroup_model->get_option_groups();

		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$settings = $this->Setting_model->settings();
		$bonuses = $this->Base_model->getDatas('tbl_bonus', null, 'events');

		$today = date('Y-m-d');
		$cur_week = $this->Base_model->getRow('tbl_week', ['from <=' =>$today, 'to >=' => $today]);
	
		$this->reply(200, 'ok', [
			'localtime' => $localtime,
			'servertime' => strtotime(date('Y-m-d H:i:s')),
			'timezone' => $this->timeZone,
			'options'=> $options, 
			'option_groups'=> $option_groups,
			'sports' => $sports,			
			// 'current_week' => $this->Week_model->current(),
			'current_week' => $cur_week,
			'settings' => $settings,
			'bonuses' => $bonuses
			]);
	}

	public function get_leagues()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$days = $request['days'];
		$cur_week = $this->Week_model->current();

		$cond = null;
		$datetime = new DateTime(date('Y-m-d'));
		$datetime->modify('+1 day');
		$tomorrow = $datetime->format('Y-m-d');

		if($days == 'today')
			$cond = ['datetime >=' => date('Y-m-d H:i:s'), 'date' =>date('Y-m-d')];
		else if($days == 'tomorrow')
		{
			if($tomorrow > $cur_week['to'])
			{
				$this->reply(200, 'ok', []);
				return;
			}
			$cond = ['date' => $tomorrow];
		}			
		else
		{
			if($tomorrow > $cur_week['to'])
				$tomorrow = $cur_week['to'];
			$cond = ['datetime >=' => date('Y-m-d H:i:s'), 'datetime <=' =>$tomorrow.' 23:59:59'];
		}

		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$totalResult = [];

		foreach($sports as $sport)
		{
			$countries = $this->Base_model->getDatas('tbl_'.$sport->key.'_country', null);
			$leagues = $this->Base_model->getDatas('tbl_'.$sport->key.'_league', null);
			$events = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', $cond);


			$result = [];
			foreach($countries as $country)
			{


				$leaguess = [];
				foreach($leagues as $lg)
				{
					if($lg->country_key != $country->key) 
						continue;
					$exist_event = false;
					foreach($events as $event)
					{
						if($event->league_key == $lg->key && json_decode($event->odds) != null)
						{
							$exist_event = true;
							break;
						}
					}
					if($exist_event)
						$leaguess []= ['id' => $lg->key, 'name' => $lg->name, 'logo' => $lg->logo, 'events' => 0];
				}
				if(count($leaguess) > 0)
					$result [] = ['id' => $country->key, 'name' => $country->name, 'iso2' => $country->iso2, 'logo'=> $country->logo, 'leagues' =>$leaguess];
			}

			$sport->countries = $result;
			$totalResult[] = $sport;
		}
		$this->reply(200, 'ok', $totalResult);
	}

	public function get_leagues_events()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$sportLeagues = $request->sports;
		$days =  $request->days;

		$today = date('Y-m-d');
		$cur_week = $this->Base_model->getRow('tbl_week', ['from <=' =>$today, 'to >=' =>$today]);

		$cond = null;
		$datetime = new DateTime(date('Y-m-d'));
		$datetime->modify('+1 day');
		$tomorrow = $datetime->format('Y-m-d');

		if($days == 'today')
			$cond = ['datetime >=' => date('Y-m-d H:i:s'), 'date <' =>$tomorrow];
		else if($days == 'tomorrow')
		{
			if($tomorrow > $cur_week->to)
			{
				$this->reply(200, 'ok', []);
				return;
			}
			$cond = ['date' => $tomorrow];
		}			
		else
		{
			if($tomorrow > $cur_week->to)
				$tomorrow = $cur_week->to;
			$cond = ['datetime >=' => date('Y-m-d H:i:s'), 'date <=' =>$tomorrow];
		}

		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$results = [];

		foreach($sportLeagues as $sportLeague)
		{
			$league_keys = $sportLeague->leagues;
			if(count($league_keys)==0) continue;

			$sport = $this->find_data($sports, $sportLeague->sport);
			if($sport==null) continue;
			$events = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', $cond, 'datetime');
			$countries = $this->Base_model->getDatas('tbl_'.$sport->key.'_country', null);
			$leagues = $this->Base_model->getDatas('tbl_'.$sport->key.'_league', null);	
			
			foreach($events as $event)
			{
				$odds = json_decode($event->odds);
				if($odds == null) continue;

				if(!in_array(strval($event->league_key), $league_keys)) continue;

				$key = $sport->id.'_'.$event->league_key;
				$league = null;
				if(isset($results[$key]))
					$league = $results[$key];
				else
				{
					$lg = $this->find_league($leagues, $event->league_key);
					$league = ['league' => $lg, 'country' => $this->find_country($countries, $event->country_key), 'days' =>[], 'sport'=>$sport];
				}
	
				$day = null;
				if(isset($league['days'][$event->date]))
					$day = $league['days'][$event->date];
				else
					$day = ['date'=>$event->date, 'events'=>[], 'week'=>$cur_week];
				$evs = $day['events'];
	
				$event->sport_id = $sport->id;
				$event->sport_name = $sport->name;
				$event->sport_key = $sport->key;
				$event->odds = $odds;							
				$evs[] = $this->format_event($event);
				$day['events'] = $evs;
				$league['days'][$event->date] = $day;
				$results[$key] = $league;
			}
		}

		$retData = [];
		foreach($results as $key => $lg)
		{
			if(count($lg['days']) == 0) continue;

			$days = [];
			foreach($lg['days'] as $key1 => $day)
			{
				$days[]= $day;
			}
			$retData [] = [
				'sport' => $lg['sport'],
				'country_name'=> $lg['country']->name, 
				'country_logo'=>$lg['country']->logo,
				'league_name'=> $lg['league']->name,
				'league_logo'=> $lg['league']->logo,
				'days' => $days
			];
		}
		$this->reply(200, 'ok', $retData);
	}

	public function get_event()
	{
		$request = json_decode(file_get_contents('php://input'), false);

		$sport_id = intval($request->qbet / 10000) + 1;
		$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
		if($sport==null)
		{
			$this->reply(402, 'invalid qbet for sport', null);
		}
		$tblFixture = 'tbl_'.$sport->key.'_fixture';

		$today = date('Y-m-d');
		$week = $this->Base_model->getRow('tbl_week', ['from <=' =>$today, 'to >=' =>$today]);

		$event = $this->Base_model->getRow($tblFixture, ['year'=>$week->year, 'week'=>$week->week, 'qbet'=>$request->qbet]);
		if($event==null)
		{
			$this->reply(402, 'invalid qbet', null);
			return;
		}
		$odds = json_decode($event->odds);		
		if($odds==null)
		{
			$this->reply(402, 'there is nor market data', null);
			return;
		}
		$event->odds = $odds;
		$event->sport_id = $sport->id;
		$event->sport_key = $sport->key;
		$this->reply(200, 'ok', $event);		
	}

	public function get_upcoming_events()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$event_count = $request->event_count;
		
		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$from_dt = date('Y-m-d H:i:s');		
		$today = date('Y-m-d');		
		$cur_week = $this->Base_model->getRow('tbl_week', ['from <=' =>$today, 'to >=' =>$today]);
		$to_dt = $cur_week->to.' 23:59:59';

		$events = [];
		foreach($sports as $sport)
		{
			$fixtures = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', 
				['datetime >=' => $from_dt, 'datetime <=' => $to_dt, 'odds <>' => ''], 'datetime', 'ASC');

			foreach($fixtures as $fixture)
			{
				$fixture->sport_id = $sport->id;
				$fixture->sport_name = $sport->name;
				$fixture->sport_key = $sport->key;
				$events[$fixture->datetime.'_'.$sport->id] = $fixture;
			}
		}

		$evs = [];
		foreach($events as $key => $data)
			$evs[]= $data;

		$results = [];
		foreach($events as $event)
		{
			$day = null;
			if(isset($results[$event->date]))
				$day = $results[$event->date];
			else
				$day = ['date'=>$event->date, 'events'=>[], 'week'=>$cur_week];
			$evs = $day['events'];
			$odds = json_decode($event->odds);
			if($odds==null) 
				continue;

			$event = $this->format_event($event);
			$evs[] = [					
					'sport_id' => $event->sport_id,
					'sport_name' => $event->sport_name,
					'sport_key' => $event->sport_key,
					'qbet' => $event->qbet,
					'date'=>$event->date, 
					'time'=>$event->time, 
					'datetime' => $event->datetime,					
					'home_team'=>$event->home_team, 
					'home_team_logo'=>$event->home_team_logo,
					'away_team'=>$event->away_team, 
					'away_team_logo'=>$event->away_team_logo,
					'odds' => $odds,
					'final_result' => $event->final_result
				];
			$day['events'] = $evs;
			$results[$event->date] = $day;
			if (count($evs) >= $event_count)
				break;
		}
		$retData = [];
		foreach($results as $key => $day)
		{
			$retData[]= $day;
		}
		$this->reply(200, 'ok', $retData);
	}



	public function get_odds()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$kind = $request->kind;
		$matchId = $request->match;
		$kind = 'football';
		
		$tblNames = $this->get_table_names($kind);
		// $userInfo = $this->logonCheck($request['token']);
		// if($userInfo==null) return; 

		$data = $this->Base_model->getRow($tblNames['fixture'], ['key'=>$matchId]);
		$result = [];
		if($data)
		{
			$marker = json_decode($data->odds, false);
			if ($marker!= null)
			{
				$row = [];
				$row["marker"] = $marker->odd_bookmakers;
				$row["odds"] = json_encode($marker);
				$result[] = $row;	
			}
		}
		$this->reply(200, 'ok', $result);		
	}


	public function get_printing_odds()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userInfo = $this->logonCheck($request['token']);	
		if($userInfo==null) return;
		
		$user = $this->Base_model->getRow('tbl_user', ['id'=>$userInfo->id]);
		if($user==null)
		{
			$this->reply(401, 'Invalid user', null);
			return;
		}

		$today = date('Y-m-d');
		$datetime = new DateTime($today);
		$datetime->modify('+1 day');
		$tomorrow = $datetime->format('Y-m-d');	

		//day condition
		$condition = [];
		$day = $request['day'];
		if($day=='today')
		{
			$condition['date'] = $today;
			$condition['datetime >='] = date('Y-m-d H:i:s');
		}
		else if($day=='tomorrow')
			$condition['date'] = $tomorrow;
		else
		{
			$condition['datetime >='] = date('Y-m-d H:i:s');
			$condition['datetime <='] = $tomorrow.' 23:59:59';
		}

		//option groups
		$option_groups = $request['option_groups'];
		$optgroups = $this->OptionGroup_model->get_option_groups();

		$opts = [];
		$optgrps = [];
		foreach($option_groups as $optgrp_id)
		{
			foreach($optgroups as $grp)
			{
				if($optgrp_id == $grp['id'])
				{
					$optgrps[] = $grp;
					foreach($grp['options'] as $opt)
						$opts[] = $opt;
					break;
				}
			}
		}

		$sports = $request['sports'];
		$sportDatas = [];
		foreach($sports as $sport_id)
		{
			$sport = $this->Base_model->getRow('tbl_sport', ['id'=>$sport_id]);
			if($sport==null) continue;

			$tbl_fixture = 'tbl_'.$sport->key.'_fixture';
			$tbl_league = 'tbl_'.$sport->key.'_league';
			$tbl_country = 'tbl_'.$sport->key.'_country';

			$leagues = $this->Base_model->getDatas($tbl_league, null);
			$countries = $this->Base_model->getDatas($tbl_country, null);
			$fixtures = $this->Base_model->getDatas($tbl_fixture, $condition, 'date');

			$leagueDatas = [];
			foreach($fixtures as $fixture)
			{
				$odds = json_decode($fixture->odds, true);
				if($odds==null || count($odds) ==0) continue;

				$leagueData = null;
				$key = $fixture->league_key.'_'.$fixture->date;
				if(isset($leagueDatas[$key]))
					$leagueData = $leagueDatas[$key];
				else
				{
					$league = $this->find_league($leagues, $fixture->league_key);
					if($league==null) continue;
					$country = $this->find_country($countries, $league->country_key);
					if($country==null) continue;					
					$leagueData = ['name'=>$league->name, 'date'=>$fixture->date, 'country'=>$country->name, 'matches'=>[]];
				}
				$matches = $leagueData['matches'];
				
				$odds = json_decode($fixture->odds, true);
				$vals = [];
				foreach($opts as $opt)
				{
					$opt_key = $opt['key'];
					if(isset($odds[$opt_key]))
						$vals[] = $odds[$opt_key];
					else
						$vals[] = '';
				}

				$match = ['qbet'=>$fixture->qbet, 
						'match'=>$fixture->home_team.' - '.$fixture->away_team, 
						'time'=>$fixture->time, 'odds'=>$vals];
				$matches[] = $match;

				$leagueData['matches'] = $matches;
				$leagueDatas[$key] = $leagueData;
			}
			$leagueArr = [];
			foreach($leagueDatas as $key=>$data)
				$leagueArr[] = $data;
			$sportDatas[] = ['sport'=>$sport->name, 'leagues'=>$leagueArr];
		}
		$this->reply(200, 'ok', ['sports'=>$sportDatas, 'option_groups'=>$optgrps]);
	}	

	public function get_latest_matches()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$count = $request['count'];

		$fixtures = [];
		foreach($sports as $sport)
		{
			$leagues = $this->Base_model->getDatas('tbl_'.$sport->key.'_league', null);		
			$countries = $this->Base_model->getDatas('tbl_'.$sport->key.'_country', null);
			$tblFixture = 'tbl_'.$sport->key.'_fixture';
			$datas = $this->Base_model->getDatas($tblFixture, ['status'=>'Finished'], 'datetime','DESC', 0, $count+1);
			foreach($datas as $data)
			{
				$data->sport_id = $sport->id;
				$data->sport_name = $sport->name;
				$data->sport_key = $sport->key;

				$league = $this->find_league($leagues, $data->league_key);
				if($league)
					$data->league = $league->name;
				else
					$data->league = '';		
					
				$country = $this->find_country($countries, $data->country_key);
				if($country)
					$data->country = $country->name;
				else
					$data->country = '';	
				$fixtures[$data->datetime.'_'.$sport->id] = $data;
			}
		}		

		$res = [];
		foreach($fixtures as $key => $fixture)
		{
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

	public function get_options()
	{
		$options = $this->Base_model->getDatas('tbl_option', null);
		$this->reply(200, 'ok', $options);
	}

	public function get_sports()
	{
		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$this->reply(200, 'ok', $sports);
	}	
	
}