<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class UserBookingApi extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->timeZone = 'Africa/Lagos';
		date_default_timezone_set($this->timeZone);
		$this->load->helper('url');
		$this->load->model('Base_model');
		$this->load->model('Calc_model');		
		$this->load->model('FormatFixture_model');
		$this->load->model('Setting_model');		
				
		
		$this->tblBet = 'tbl_bet';
		$this->tblBetBook = 'tbl_bet_book';
		$this->tblSport = 'tbl_sport';
		$this->tblOption = 'tbl_option';
	}
	protected function reply($status, $message, $data)
	{
		$result = array('status'=>$status, 'message'=>$message, 'data'=>$data);
		echo json_encode($result);
	}	

	protected function logonCheck($token)
	{
		$userInfo = $this->checkToken($token);
		if($userInfo==null)
		{
			echo json_encode(['status'=>401, 'message'=>'Unauthorized, invalid token!', 'data'=>null]);
			return null;
		}
		return $userInfo;
	}	

	function find_fixture($lst, $qbet, $date)
	{
		foreach($lst as $itm)
		{
			if($itm->qbet == $qbet && $itm->date == $date)
				return $itm;
		}
		return null;
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

	public function make_book()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		// $userInfo = $this->logonCheck($request['token']);	
		// if($userInfo==null) return;

		$kind = strval($request['kind']);		
		$sports = $this->Base_model->getDatas('tbl_sport', null);
		$sport = null;
		foreach($sports as $spt)
		{
			if($spt->key == $kind)
			{
				$sport = $spt;
				break;
			}
		}
		if($sport == null)
		{
			return $this->reply(402, 'Invalid sport', null); return;
		}

		$settings = $this->Setting_model->settings();
		$events = $request['events'];
		if(intval($request['amount']) > intval($settings['max_stake']) )
		{
			return $this->reply(402, "Amount can't big than max stake ". $settings['max_stake'], null); return;
		}
		if(count($events) < intval($settings['min_events_per_ticket']) )
		{
			return $this->reply(402, 'Please choose at least '. $settings['min_events_per_ticket'].' evetns!', null); return;
		}


		// check events
		$fixtures = $this->Base_model->getDatas('tbl_'.$sport->key.'_fixture', ['date >=' => date('Y-m-d')]);
		$options = $this->Base_model->getDatas('tbl_option', null);

		$bet_expire = date('Y-m-d H:i:s');
		foreach($events as $ev)
		{
			$event = $this->find_fixture($fixtures, $ev['event'], $ev['date']);
			if($event==null)
			{
				return $this->reply(402, 'Include invalid event', null); return;
			}
			$option = $this->data_by_id($options, $ev['opt']);
			if($option==null)
			{
				return $this->reply(402, 'Include invalid option', null); return;
			}
			$expire = $event->date.' '.$event->time;
			if($expire > $bet_expire)
				$bet_expire = $expire;
		}

		$under = $request['under'];
		$amount = $request['amount'];

		$bet_id = sprintf('9%010u', microtime(true) * 1000);
		$data = [
			'bet_id'=>$bet_id, 
			'under'=> $under, 
			'amount' => $amount,
			'events'=>json_encode($events), 'bet_time'=>date('Y-m-d H:i:s'),
			'expire' => $bet_expire
		];
		$this->Base_model->insertData($this->tblBetBook, $data);

		// update user table
		$this->reply(200, 'ok', $bet_id);
	}

	public function get_book()
	{
		$request = json_decode(file_get_contents('php://input'), true);
		// $userInfo = $this->logonCheck($request['token']);	
		// if($userInfo==null) return;

		$book_id = $request['book_id'];
		// $bet = $this->Base_model->getRow($this->tblBetBook, ['bet_id' => $book_id, 'user_id'=>$userInfo->id]);
		$bet = $this->Base_model->getRow($this->tblBetBook, ['bet_id' => $book_id]);
		if($bet == null)
		{
			$this->reply(402, 'Unknown book id', null);
			return;
		}

		$cur_dt = date('Y-m-d H:i:s');
		if($cur_dt > $bet->expire)
		{
			return $this->reply(402, 'Incuding expired events', null);			
		}

		$sports = $this->Base_model->getDatas($this->tblSport, null);
		$options = $this->Base_model->getDatas($this->tblOption, null);
		
		$evs = [];
		$events = json_decode($bet->events);
		$include_expired_event = false;

		foreach($events as $ev) 
		{
			$sport = $this->data_by_id($sports, $ev->sport);
			if($sport == null)
				return $this->reply(402, 'Incuding invalid events', null);

			$tbl = 'tbl_'.$sport->key.'_fixture';
			$game = $this->Base_model->getRow($tbl, ['qbet' => $ev->event, 'date'=> $ev->date]);
			$date_time = $game->date.' '.$game->time;
			if($cur_dt > $date_time)
			{
				$include_expired_event = true;
				break;
			} 

			$odds = json_decode($game->odds, true);
			$option = $this->data_by_id($options, $ev->opt);			

			$evs[] = [
				'sport' => $sport->id,
				'sport_key' => $sport->key,
				'qbet' => $ev->event,
				'event_date' => $game->date,
				'event_time' => $game->time,
				'event_title' => $game->home_team.' - '.$game->away_team,
				'option_id' => $option->id,
				'option_name' => $option->name,
				'result_time' => $ev->time,
				'value' => $odds[$option->key]
			];
		}

		if($include_expired_event == true)
		{
			return $this->reply(402, 'Incuding expired events', null);
		}

		$coupone = [
			'events' => $evs,
			'amount' => $bet->amount,
			'under' => $bet->under
		];
		$this->reply(200, 'ok', $coupone);
	}


}