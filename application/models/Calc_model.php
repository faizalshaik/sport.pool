<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calc_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	function calcLines($under, $events)
	{
		$val =1 ;
		$division = 1;
		for($i=0; $i<$under; $i++)
		{
			$val = $val * ($events - $i);
			$division = $division * ($i + 1);
		}

		$lines = $val / $division;
		return $lines; 
	}


	function combination(&$output, $arr, $index, $n, $r, $target)
	{
		if($r == 0)
		{
			$newEntry = [];
			for($i=0; $i<$index; $i++)
				$newEntry[] = $arr[$i];

			$output[]= $newEntry;		
		}
		else if ($target == $n) 
		{
			return $output;
		}
		else {
			$arr[$index] = $target;
			$this->combination($output, $arr, $index + 1, $n, $r - 1, $target + 1);
			$this->combination($output, $arr, $index, $n, $r, $target + 1);
		}
		return $output;
	}

	function calcPrizeSum($games, $under, $events, $max)
	{
		$combines = [];
		$arr = array_fill(0, $events, 0);
		$this->combination($combines, $arr, 0, $events, $under, 0);
		
		$pzSum = 0;
		foreach($combines as $idxs)
		{
			$val = 1;			
			foreach($idxs as $idx)
			{
				$game = $games[$idx];
				if($max == true || $game['state']=='win')
				{
					$val = $val * $game['prize'];
				}
				else
				{
					$val = 0;
					break;
				}
			}
			$pzSum = $pzSum + $val;
		}
		return $pzSum;
	}


	// gamelist [] = [key=>9080, state=>'win', prize=>2.09]
	public function calc_win($gamelist, $under, $amount, $max = true)
	{
		$events = count($gamelist);		
		if(!$max)
		{
    		$woncount = 0 ;
    		foreach($gamelist as $game)
    		{
    			if($game['state'] == 'win')
    				$woncount ++;
    		}
    		if($woncount < $under)
    		    return 0;
		}

		$lines = $this->calcLines($under, $events);
		$apl = $amount / $lines;

		$prizeSum = $this->calcPrizeSum($gamelist, $under, $events, $max);
		$win = $prizeSum * $apl;
		return $win;
	}

}


