<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class FootballApi extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->timeZone = 'Africa/Lagos';
		date_default_timezone_set($this->timeZone);
		set_time_limit(120);

		$this->load->helper('url');
		$this->load->model('Base_model');
		$this->load->model('Week_model');

		// $this->ApiKey = '7b01d8b488dd565c86d98e98ce8a7aa0c8b934ee231a660d3ac9d171882f1ed8';
		$this->ApiKey = '901a35b6e8b1361398c7da74c9fef6307ecccd2912a2cee74c825511e536d246';
		$this->BaseUrl = "https://allsportsapi.com/api/football/";
		$this->tblCountry = 'tbl_football_country';
		$this->tblLeague = 'tbl_football_league';
		$this->tblFixture = 'tbl_football_fixture';
		$this->tblOdd = 'tbl_football_odd';
		
		ini_set('memory_limit', '1024M'); // or you could use 1G		
	}
	public function test()
	{
		echo date('Y-m-d H:i:s');
	}

	private function build_url($params)
	{
		$params["APIkey"] = $this->ApiKey;
		$pas = [];
		foreach($params as $key=>$val)
		{
			$pas[] = $key.'='.$val;
		}
		return $this->BaseUrl.'?'.implode ("&",$pas);
	}

	private function http_get($url)
	{
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		// curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		// $postData = ['extra_fields'=>['location', 'postcode'], "segment_name"=> "Subscribed Users"];
		// $postData = ['extra_fields'=>['location']];
		// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
		$response = curl_exec($ch); 
		curl_close($ch); 
		return $response;
	}


	public function get_countries()
	{
		$url = $this->build_url(["met"=>"Countries"]);
		$res = $this->http_get($url);
		$data = json_decode($res, false);
		if($data == null || $data->success != 1) 
		{		
			// $this->log('call export_csv failed!');
			return;
		}

		$this->Base_model->truncate($this->tblCountry);
		foreach($data->result as $country)
		{
			$this->Base_model->insertData($this->tblCountry, 
				[
					'key'=> $country->country_key,
					'name' => $country->country_name,
					'iso2' => $country->country_iso2,
					'logo' => $country->country_logo
				]);
		}
	}

	public function get_leagues()
	{
		$url = $this->build_url(["met"=>"Leagues"]);
		$res = $this->http_get($url);
		$data = json_decode($res, false);
		if($data == null || $data->success != 1) 
		{		
			// $this->log('call export_csv failed!');
			return;
		}

		$this->Base_model->truncate($this->tblLeague);
		foreach($data->result as $league)
		{
			$this->Base_model->insertData($this->tblLeague, 
				[
					'key'=> $league->league_key,
					'name' => $league->league_name,
					'country_key' => $league->country_key,
					'logo' => $league->league_logo
				]);
		}
	}	


	private function get_week($weeks, $date)
	{
		foreach($weeks as $week)
		{
			if($date < $week->from) continue;
			if($date > $week->to) continue;
			return $week;
		}
	}

	public function get_events()
	{
		$curWeek = $this->Week_model->current();
		$url = $this->build_url(["met"=>"Fixtures", 'from'=>$curWeek['from'], 'to'=>$curWeek['to'], 'timezone'=>$this->timeZone]);
		$res = $this->http_get($url);


		$data = json_decode($res, false);
		if($data == null || $data->success != 1 || !isset($data->result)) 
		{		
			// $this->log('call export_csv failed!');
			echo $res;
			return;
		}

		// $this->Base_model->truncate($this->tblFixture);
		$max_qbets = [];
		$keys = [];
		$events = $this->Base_model->getDatas($this->tblFixture, ['date >=' => $curWeek['from']]);
		foreach($events as $ev)
		{
			$keys[$ev->key] = 1;
			$wk = $ev->year.'_'.$ev->week;
			if(isset($max_qbets[$wk]))
			{
				if($ev->qbet > $max_qbets[$wk])
					$max_qbets[$wk] = $ev->qbet;
			}
			else
			{
				$max_qbets[$wk] = $ev->qbet;
			}
		}
		
		$weeks = $this->Base_model->getDatas('tbl_week', ['from >=' => $curWeek['from']]);

		$insert_data_arr = [];
		$update_data_arr = [];
		foreach($data->result as $ev)
		{
			$week = $this->get_week($weeks, $ev->event_date);
			if($week==null) continue;

			$new_data = [
				'year' => $week->year,
				'week' => $week->week,
				'key'=> $ev->event_key,
				'date' => $ev->event_date,
				'time' => $ev->event_time,
				'datetime' => $ev->event_date.' '.$ev->event_time,
				'home_team' => $ev->event_home_team,
				'home_team_key' => $ev->home_team_key,
				'away_team' => $ev->event_away_team,
				'away_team_key' => $ev->away_team_key,
				'halftime_result' => $ev->event_halftime_result,
				'final_result' => $ev->event_final_result,
				'ft_result' => $ev->event_ft_result,
				'penalty_result' => $ev->event_penalty_result,
				'status' => $ev->event_status,
				'league_key' => $ev->league_key,
				'league_round' => $ev->league_round,
				'league_season' => $ev->league_season,
				'live' => $ev->event_live,
				'stadium' => $ev->event_stadium,
				'referee' => $ev->event_referee,
				'home_team_logo' => $ev->home_team_logo,
				'away_team_logo' => $ev->away_team_logo,
				'country_key' => $ev->event_country_key,
				'home_formation' => $ev->event_home_formation,
				'away_formation' => $ev->event_away_formation,
				'goalscorers' => json_encode($ev->goalscorers),
				'cards' => json_encode($ev->cards),
				'statistics' => json_encode($ev->statistics) 
			];

			if(isset($keys[$ev->event_key]))
				$update_data_arr []= $new_data;
			else
			{
				$wk = $week->year.'_'.$week->week;
				$qbet = 100;
				if(isset($max_qbets[$wk]))
					$qbet = $max_qbets[$wk];
				$qbet = max(101, $qbet + 1);
				$new_data['qbet'] = $qbet;
				$max_qbets[$wk] = $qbet;
				$insert_data_arr[] = $new_data;
			}				
		}

		if(count($update_data_arr) > 0)
			$this->Base_model->updateBatch($this->tblFixture, $update_data_arr, 'key');

		if(count($insert_data_arr) > 0)
			$this->Base_model->insertBatch($this->tblFixture, $insert_data_arr);
	}

	// private function get_odds_by_country($country)
	// {
	// 	$curWeek = $this->Week_model->current();
	// 	$url = $this->build_url(["met"=>"Odds", 'from'=>$curWeek['from'], 'to'=>$curWeek['to'], 'timezone'=>$this->timeZone, 'countryId'=>$country]);
	// 	$res = $this->http_get($url);

	// 	$update_data_arr = [];
	// 	$data = json_decode($res, false);
	// 	if($data != null && $data->success == 1 && isset($data->result)) 
	// 	{
	// 		foreach($data->result as $matchid => $oddArr)
	// 		{
	// 			$odds = null;
	// 			foreach($oddArr as $bookmarker)
	// 			{
	// 				if($bookmarker->odd_bookmakers == "Interwetten")
	// 				{
	// 					$odds = $bookmarker;
	// 					break;
	// 				}
	// 			}
	// 			$update_data_arr[]= ['key' => $matchid, 'odds' => json_encode($odds)];
	// 		}	
	// 	}
	// 	if (count($update_data_arr) >0 )
	// 		$this->Base_model->updateBatch($this->tblFixture, $update_data_arr, 'key');

	// }

	// public function get_odds($start, $count)
	// {	
	// 	$cuntries = $this->Base_model->getDatas('tbl_football_country', null, 'key');
	// 	if($start >= count($cuntries)) return;
	// 	$to = min($start + $count, count($cuntries));

	// 	for($i = $start; $i < $to; $i++)
	// 	{
	// 		$this->get_odds_by_country($cuntries[$i]->key);
	// 	}
	// }


	public function get_odds_by_country($country, $options = null)
	{
		$curWeek = $this->Week_model->current();
		$url = $this->build_url(["met"=>"Odds", 'from'=>$curWeek['from'], 'to'=>$curWeek['to'], 'timezone'=>$this->timeZone, 'countryId'=>$country]);
		$res = $this->http_get($url);

		$update_data_arr = [];
		$data = json_decode($res, true);
		if($data != null && $data['success'] == 1 && isset($data['result'])) 
		{
			foreach($data['result'] as $matchid => $oddArr)
			{
				$odds = [];
				foreach($options as $opt)
					$odds[$opt] = null;

				$oddDatas = null;
				//fill Interwetten
				foreach($oddArr as $bookmarker)
				{
					if($bookmarker['odd_bookmakers'] == 'Interwetten')
					{
						$oddDatas = $bookmarker;
						foreach($options as $opt)
							$odds[$opt] = $oddDatas[$opt];
						break;
					}
				}

				//fill others if null
				foreach($oddArr as $bookmarker)
				{
					if($bookmarker['odd_bookmakers'] == 'Interwetten') continue;

					$oddDatas = $bookmarker;
					foreach($options as $opt)
					{
						if($odds[$opt] == null && $oddDatas[$opt] != null )
							$odds[$opt] = $oddDatas[$opt];
					}						
				}
				//echo json_encode($odds);
				$update_data_arr[]= ['key' => $matchid, 'odds' => json_encode($odds)];
			}	
		}
		if (count($update_data_arr) >0 )
			$this->Base_model->updateBatch($this->tblFixture, $update_data_arr, 'key');
	}

	public function get_odds($start, $count)
	{	
		$opts = [];
		$options = $this->Base_model->getDatas('tbl_option', null);
		foreach($options as $opt)
			$opts[] = $opt->key;

		$cuntries = $this->Base_model->getDatas('tbl_football_country', null, 'key');
		if($start >= count($cuntries)) return;
		$to = min($start + $count, count($cuntries));

		for($i = $start; $i < $to; $i++)
		{
			$this->get_odds_by_country($cuntries[$i]->key, $opts);
		}
	}

}