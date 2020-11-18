<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class AdminBetCalc_api extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->timeZone = 'Africa/Lagos';
		date_default_timezone_set($this->timeZone);
		$this->load->helper('url');
		$this->load->model('Base_model');
		$this->load->model('FormatFixture_model');
		$this->load->model('ResultGoal_model');		
		$this->load->model('Calc_model');
	}

	public function test()
	{
		$today = date('Y-m-d H:i:s');
		$datetime = new DateTime($today);
		$datetime->modify('-2 hours');

		$base_dt = $datetime->format('Y-m-d H:i:s');
		echo $today;
		echo $base_dt;

	}

	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
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


	function calc_results()
	{
		$request = json_decode(file_get_contents('php://input'), false);
		$userInfo = $this->logonCheck($request->token);	
		if($userInfo==null) return;

		if($userInfo->role !='admin')
		{
			$this->reply(401, "permission is not allowed", null);
			return;
		}
		$this->make_result();
	}

	private function find_fixture($fixtures, $qbet, $date)
	{
		foreach($fixtures as $fixture)
		{
			if($fixture->qbet == $qbet && $fixture->date == $date)
				return $fixture;
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


	private function make_result()
	{
		$bets = $this->Base_model->getDatas('tbl_bet', ['bet_result'=>'none'], 'insert_at');
		if(count($bets) ==0 )
			return;
		$baseDay =  date('Y-m-d', strtotime($bets[0]->insert_at)); 

		$fixtures = [];
		$sports = $this->Base_model->getDatas('tbl_sport', null);
		foreach($sports as $sport)
		{
			$fixtures[$sport->id] = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', ['date >=' => $baseDay]);
		}
		$bonuses = $this->Base_model->getDatas('tbl_bonus', null);

		$result_list = [];
		foreach($bets as $bet)
		{
			$results = json_decode($bet->results);
			$invalid = false;
			$not_finished = false;
			$bet_results = [];
			$gamelist = [];
			$minprize = 1000;

			foreach($results as $result)
			{
				$fixture = $this->find_fixture($fixtures[$result->sport->id], $result->fixture->qbet, $result->fixture->date);
				if($fixture == null)
				{					
					$invalid = true;
					break;
				}
				else if($fixture->final_result == '' || $fixture->final_result == '-')
				{
					$not_finished = true;
					break;
				}
				$fixture_fmt = $this->FormatFixture_model->format($fixture);
				$fixture_fmt['league'] = $result->fixture->league;
				$fixture_fmt['country'] = $result->fixture->country;

				$win_fixture = false;
				if($result->option->relation == 'goal')
					$win_fixture = $this->ResultGoal_model->is_win($result->option->key, $fixture_fmt['ft_result'][0],$fixture_fmt['ft_result'][1]);

				$state = 'lose';
				if($win_fixture) $state = 'win';

				// $odds = json_decode($fixture->odds, true);
				$result->fixture = $fixture_fmt;
				$result->state = $state;
				$bet_results[] = $result;
				$gamelist[] = ['key' => $fixture_fmt['qbet'], 'state'=>$state, 'prize'=> $result->prize];

				if($result->prize < $minprize) $minprize = $result->prize;
			}
			if($not_finished ==true) 
				continue;

			if($invalid==false)
			{
				// calc bet result
				$won = $this->Calc_model->calc_win($gamelist, $bet->under, $bet->amount, false);

				//calc bonus
				$bonus_percent = 0;
				$bonus = 0;
				$bet_result = 'lost';
				if($won > 0){
					$bet_result = 'won';
					if($bet->under == $bet->event_count)
					{
						$percent = $this->get_bonus_percent($bonuses, $bet->event_count, $minprize);
						$bonus = $won * $percent / 100;
						$bonus_percent = $percent;
					}
					 
				}
				$result_list[] = ['bet_id'=> $bet->bet_id, 'bet_result'=>$bet_result, 'bonus' => $bonus,
					'bonus_percent'=> $bonus_percent, 'won_amount'=>$won, 'total_win' => $won + $bonus,
					'results'=>json_encode($bet_results),'result_time' =>date('Y-m-d H:i:s') ];
			}			
			else
			{
				$result_list[] = ['bet_id'=> $bet->bet_id, 'bet_result'=>'invalid', 'result_time' =>date('Y-m-d H:i:s') ];
			}			
		}

		if(count($result_list) > 0)
		{
			$this->Base_model->updateBatch('tbl_bet', $result_list, 'bet_id');
		}
		$this->reply(200, "ok", null);
	}
}