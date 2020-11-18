<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FilterBet_model extends CI_Model {
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Base_model');		
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

	public function filter($bet, $filter, $operators, $agencies, $users)
	{
		if($bet->insert_at < $filter->date_from) return false;
		if($bet->insert_at > $filter->date_to) return false;
		if($filter->betid >0 && $bet->bet_id != $filter->betid) return false;
		if($filter->type !='' && $bet->type!=$filter->type) return false;
		if($filter->amount_min >0 && $bet->amount < $filter->amount_min) return false;
		if($filter->payout_min >0 && $bet->won_amount < $filter->payout_min) return false;

		if($filter->state_void || $filter->state_in_progress || $filter->state_lost || $filter->state_won)
		{
			$matched = false;
			if($filter->state_void && $bet->status==2)$matched = true;

			if($bet->status==1)
			{
				if($matched==false && $filter->state_in_progress && $bet->bet_result=='none')$matched = true;
				if($matched==false && $filter->state_lost && $bet->bet_result=='lost')$matched = true;
				if($matched==false && $filter->state_won && $bet->bet_result=='won')$matched = true;	
			}
			if(!$matched)return false;			
		}

		$events = json_decode($bet->events, false);					
		if($filter->events_max > 0)
		{
			if(count($events) < $filter->events_min || count($events) > $filter->events_max)
				return false;
		}

		if($filter->qbet > 0)
		{
			$bInclude = false;			
			foreach($events as $event)
			{
				if($event->event == $filter->qbet)
				{
					$bInclude = true;
					break;
				}
			}
			if(!$bInclude) return false;
		}

		if($filter->option > 0)
		{
			$bInclude = false;			
			foreach($events as $event)
			{
				if($event->opt == $filter->option)
				{
					$bInclude = true;
					break;
				}
			}
			if(!$bInclude) return false;
		}

		if($operators !=null && $agencies!=null && $users!=null)
		{
			if($filter->user > 0)
			{
				if($bet->user_id != $filter->user)
					return false;
			}
			else if($filter->agency > 0)
			{
				if(!isset($users[$bet->user_id])) return false;
				$user = $users[$bet->user_id];
				if(!$user) return false;
				$ag = $this->data_by_id($agencies, $user->parent_id);
				if($ag == null || $ag->id != $filter->agency) return false;
			}
			else if($filter->operator > 0)
			{
				if(!isset($users[$bet->user_id])) return false;
				$user = $users[$bet->user_id];			
				if(!$user) return false;
				$ag = $this->data_by_id($agencies, $user->parent_id);
				if($ag == null) return false;
				$op = $this->data_by_id($operators, $ag->parent_id);
				if($op == null || $op->id != $filter->operator) return false;
			}	
		}
		
		return true;
	}

}


