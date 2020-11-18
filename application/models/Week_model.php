<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Week_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	public function first_monday_of_year($year)
	{
		$dt = $year.'-01-01';
		return date('Y-m-d', strtotime('next monday', strtotime($dt)));
	}

	public function current()
	{
		return $this->get_by_date(date('Y-m-d'));
	}

	function dateDifference($date_1 , $date_2 , $differenceFormat = '%a' )
	{
		$datetime1 = date_create($date_1);
		$datetime2 = date_create($date_2);	   
		$interval = date_diff($datetime1, $datetime2);	   
		return $interval->format($differenceFormat);	   
	}

	public function get_by_date($dt)
	{
		$this_year = intval(date('Y', strtotime($dt)));
		$first_monday = $this->first_monday_of_year($this_year);
		if($dt >= $first_monday)
		{
			$days = $this->dateDifference($dt, $first_monday);
		}
		else
		{
			$this_year = $this_year - 1;
			$first_monday = $this->first_monday_of_year($this_year);
			$days = $this->dateDifference($dt, $first_monday);
		}

		$week = intval($days/7) + 1;
		$from = new DateTime($first_monday);
		$from->modify('+'.(($week-1) * 7).' day');
		$from_dt = $from->format('Y-m-d');
		$from->modify('+6 day');
		$to_dt = $from->format('Y-m-d');
		return ['year'=>$this_year, 'week'=> $week, 'from'=>$from_dt, 'to'=>$to_dt];
	}

	public function get_by_id($year, $id)
	{
		$this_year = intval($year);
		$first_monday = $this->first_monday_of_year($this_year);
		$days = ($id-1) * 7 + 1;

		$datetime = new DateTime($first_monday);
		$datetime->modify('+'.$days.' day');
		$dt = $datetime->format('Y-m-d');
		return $this->get_by_date($dt);		
	}

}


