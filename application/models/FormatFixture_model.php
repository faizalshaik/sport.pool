<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FormatFixture_model extends CI_Model {
	public function __construct()
	{
		parent::__construct();
	}

	private function format_score($score)
	{
		$datas = explode('-', $score);
		if(count($datas) != 2)
		{
			return [0, 0];
		}
		return [intval(trim($datas[0], "  ")),  intval(trim($datas[1], "  "))];
	}

	private function format_goals($jsondata)
	{
		$goals = [];
		$goalScorers = json_decode($jsondata, false);
		if(!$goalScorers) return $goals;
		foreach($goalScorers as $goal)
		{
			if($goal->home_scorer !="")
			{
				$goals[] = ['competitor'=> $goal->home_scorer, 'player'=>'Home', 'time' => $goal->time, 'type'=>$goal->score_info];
			}
			else
			{
				$goals[] = ['competitor'=> $goal->away_scorer, 'player'=>'Away', 'time' => $goal->time, 'type'=>$goal->score_info];
			}
		}	
		return $goals;
	}

	private function format_cards($jsondata)
	{
		$res = [];
		$cards = json_decode($jsondata, false);
		if(!$cards) return $res;

		foreach($cards as $card)
		{
			$ctype = 'yellow';
			if($card->card == "red card")
				$ctype = 'red';

			if($card->home_fault !="")
			{
				$res[] = ['competitor'=> $card->home_fault, 'player'=>'Home', 'time' => $card->time, 'type'=>$ctype];
			}
			else
			{
				$res[] = ['competitor'=> $card->home_fault, 'player'=>'Away', 'time' => $card->time, 'type'=>$ctype];
			}
		}
		return $res;
	}

	private function format_conner($jsondata)
	{
		$res = [];
		$ents = json_decode($jsondata, false);
		if(!$ents) return [0,0];
		
		foreach($ents as $ent)
		{
			if($ent->type =="Corner Kicks")
			{
				$res[] = $ent->home;
				$res[] = $ent->away;
			}
		}

		if(count($res) < 2)
			$res = [0,0];
		return $res;
	}	

	public function format($event)
	{
		$ht_result = $this->format_score($event->halftime_result);
		$ft_result = $this->format_score($event->final_result);
		$st_result = [$ft_result[0] - $ht_result[0], $ft_result[1] - $ht_result[1]];
		$goals = $this->format_goals($event->goalscorers);
		$cards = $this->format_cards($event->cards);
		$conners = $this->format_conner($event->statistics);

		return [
			'qbet' => $event->qbet,
			'home_team' => $event->home_team,
			'away_team' => $event->away_team,			
			'date' => $event->date,
			'time' => $event->time,
			'datetime' => $event->datetime,
			'ht_result' => $ht_result,
			'st_result' => $st_result,
			'ft_result' => $ft_result,
			'goals' => $goals,
			'cards' => $cards,
			'conners' => $conners
		];
	}

}


