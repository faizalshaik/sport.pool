<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FormatBetEvents_model extends CI_Model {
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Base_model');		
	}

	public function format($events_json, $options, $fixtures)
	{
		$evs = [];
		$events = json_decode($events_json, false);
		foreach($events as $ev) 
		{
			$game = $fixtures[$ev->event];
			$option = $options[$ev->opt];
			$evs[] = [
				'event_key' => $ev->event,
				'event_date' => $game->date,
				'event_time' => $game->time,
				'event_title' => $game->home_team.' - '.$game->away_team,
				'option_id' => $option->id,
				'option_name' => $option->name,
				'value' => $ev->prize
			];
		}
		return $evs;
	}

}


