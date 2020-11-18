<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TerminalPrinting_model extends CI_Model {
	public function __construct()
	{
		parent::__construct();
		$this->metrics = [
			'A'=>8.5, 'B'=>8.5, 'C'=>8.5, 'D'=>8.5, 'E'=>7.5, 'F'=>7.5, 'G'=>8.5, 'H'=>9.5, 'I'=>3.5, 
			'J'=>7.5, 'K'=>8.5, 'L'=>6.5, 'M'=>11.5, 'N'=>9.5, 'O'=>8.5, 'P'=>8.5, 'Q'=>8.5, 'R'=>7.5, 
			'S'=>7.5, 'T'=>7.5, 'U'=>8.5, 'V'=>8.5, 'W'=>11.5, 'X'=>8.5, 'Y'=>7.5, 'Z'=>7.5, 'a'=>6.5, 
			'b'=>7.5, 'c'=>6.5, 'd'=>7.5, 'e'=>6.5, 'f'=>4.5, 'g'=>7.5, 'h'=>7.5, 'i'=>3.5, 'j'=>3.5, 
			'k'=>6.5, 'l'=>3.5, 'm'=>10.5, 'n'=>7.5, 'o'=>7.5, 'p'=>7.5, 'q'=>7.5, 'r'=>4.5, 's'=>6.5, 
			't'=>4.5, 'u'=>7.5, 'v'=>6.5, 'w'=>9.5, 'x'=>6.5, 'y'=>6.5, 'z'=>6.5, '0'=>7.5, '1'=>7.5, 
			'2'=>7.5, '3'=>7.5, '4'=>7.5, '5'=>7.5, '6'=>7.5, '7'=>7.5, '8'=>7.5, '9'=>7.5, '~'=>8.5, 
			'!'=>3.5, '@'=>11.5, '#'=>7.5, '$'=>7.5, '%'=>9.5, '^'=>5.5, '&'=>8.5, '*'=>5.5, '('=>4.5, 
			')'=>4.5, '['=>3.5, ']'=>3.5, '.'=>3.5, ','=>3.5, "'"=>2.5, ':'=>3.5, ' '=>3.6, '-'=>4.5, 
			'<'=>6.5, '>'=>6.5, '/'=>5.5, '?'=>6.5]; 
		$this->line_width = 173;
		$this->space_width = 3.6;
		$this->line_spliter = '-----------------------------------------';
	}

	private function calc_line_width($strLine)
	{
		$arr = str_split($strLine);
		$width = 0;
		foreach($arr as $ch)
		{
			if(isset($this->metrics[$ch])==false)
				$width += 10;
			else
				$width += $this->metrics[$ch];
		}
		return $width;
	}
	private function make_expand_content($strval, $cur_width, $expand_width)
	{
		$space_width = $expand_width - $cur_width;
		$count = $space_width / $this->space_width;
		$retVal = $strval;
		for($i=0; $i<$count; $i++)
		{
			$retVal = $retVal.' ';
		}
		return $retVal;
	}

	private function split_string_by_width($strval, $clip_width=0)
	{
		if($clip_width==0)
			$clip_width = $this->line_width;

		$lines = [];
		$arr = str_split($strval);
		$width = 0;
		$line = '';
		foreach($arr as $ch)
		{
			if(isset($this->metrics[$ch]))
				$ch_width = $this->metrics[$ch];
			else
				$ch_width = 10;

			if($width + $ch_width > $clip_width )
			{
				$lines[] = $line;	
				$line = '';
				$width = 0;
			}
			$width += $ch_width;
			$line = $line.$ch;
		}
		if($line!='')
			$lines[]= $line;
		return $lines;
	}

	public function split_to_idented_lines($strval)
	{
		$lns = $this->split_string_by_width($strval, $this->line_width-8);
		for($i=1; $i<count($lns); $i++)
		{
			$lns[$i] = '  '.$lns[$i];
		}
		return $lns;
	}

	private function make_clip_contents($strval, $clip_width)
	{
		$lines = [];
		$width = $this->calc_line_width($strval);		
		if($width <= $clip_width)
		{
			$lines[] = $this->make_expand_content($strval, $width, $clip_width);
			return $lines; 
		}

		$lns = $this->split_string_by_width($strval, $clip_width);
		foreach($lns as $ln)
		{
			$width = $this->calc_line_width($ln);
			$lines[] = $this->make_expand_content($ln, $width, $clip_width);
		}
		return $lines;
	}

	public function make_columned_contents($cnt_arr, $width_arr, $delimit)
	{
		$results = [];
		$cols = [];
		$max_line = 0;
		$nCols = count($cnt_arr);

		for($i=0; $i<$nCols-1; $i++)
		{
			$lns = $this->make_clip_contents($cnt_arr[$i], $width_arr[$i]);
			$cols[$i] = $lns;
			if(count($lns) > $max_line)
				$max_line = count($lns);			
		}
		$lns = $this->split_string_by_width($cnt_arr[$nCols-1], $width_arr[$nCols-1]);
		$cols[$i] = $lns;
		if(count($lns) > $max_line)
			$max_line = count($lns);


		for($iLine = 0; $iLine < $max_line; $iLine++)
		{
			$col_strs = [];
			for($i=0; $i<$nCols-1; $i++)
			{
				$lns = $cols[$i];
				$str_col = '';
				if($iLine < count($lns)) 
					$str_col = $lns[$iLine];
				else
					$str_col = $this->make_expand_content('', 0, $width_arr[$i]);
				$col_strs[]= $str_col;
			}

			$lns = $cols[$nCols-1];
			if($iLine < count($lns)) 
				$col_strs[]= $lns[$iLine];
			$results[] = implode($delimit, $col_strs);
		}
		return $results;
	}

	public function format_bet_full($user, $bet)
	{
		$lines  = [];
		$lines[] = 'Bet ID  '.$bet->bet_id;
		$lines[] = '             BetTwoStar';
		$lines[] = '             Week'.$bet->week;
		$lines[] = 'Player:       '.$user->surname. ' '.$user->name;
		$lines[] = 'Terminal ID: '.$user->login;
		$lines[] = 'Bet time: '.date('m/d/y g:i A', strtotime($bet->insert_at));
		$lines[] = 'Last match: '.date('m/d/y g:i A', strtotime($bet->expire_at));

		$results = json_decode($bet->results, false);		
		foreach($results as $result)
		{
			$templn = strval($result->fixture->qbet). '  '.$result->fixture->home_team.'  '.$result->fixture->away_team;
			$width = $this->calc_line_width($templn);
			if($width <= $this->line_width)
				$lines[] = $templn;
			else
			{
				// $nlns = $this->make_columned_contents(
				// 	[strval($result->fixture->qbet), 
				// 	$result->fixture->home_team, 
				// 	$result->fixture->away_team], 
				// 	[40, 62, 62], '  ');	
				$nlns = $this->make_columned_contents(
					[strval($result->fixture->qbet), 
					$result->fixture->home_team.' : '.$result->fixture->away_team], 
					[40, 128], ' ');
				foreach($nlns as $line)$lines[] = $line;
			}
			$lines[] = '        Sign: '.$result->option->name.'('.$result->option->group.') '.number_format($result->prize, 2);
		}

		$bonus = 0;
        if($bet->type=='Direct')
          $bonus = $bet->bonus_percent * $bet->max_win / 100;

		$total_win = 0;
		$status = '';
        if($bet->bet_result=='none')
        {
          $total_win = $bet->max_win + $bonus;
          $status = "inProgress";
        }
        else if($bet->bet_result=='lost')
        {
          $total_win = 0;
          $status = "Lost";
        }
        else if($bet->bet_result=='won')
        {
          $total_win = 0;
          $status = "Won";
		}

		$lines[] = 'Status:    '.$status;
		$lines[] = 'Type: '.$bet->type.'   Under: '.$bet->under;
		$lines[] = 'Amount: # '.number_format($bet->amount, 0);
		if($bet->type=='Direct')
			$lines[] = 'Pos.Win: # '.number_format($bet->max_win, 2);
		else
			$lines[] = 'Max Win: # '.number_format($bet->max_win, 2);

		$lines[] = 'Bonus:    # '.number_format($bonus, 2);
		$lines[] = 'Total Win: # '.number_format($total_win, 2);
		$lines[] = "\n\n\n";
		return implode("\n", $lines);
	}	

	public function format_bet_short($user, $bet)
	{
		$lines  = [];
		$lines[] = 'Bet ID  '.$bet->bet_id;
		$lines[] = '             BetTwoStar';
		$lines[] = '             Week'.$bet->week;
		$lines[] = 'Player:       '.$user->surname. ' '.$user->name;
		$lines[] = 'Terminal ID: '.$user->login;
		$lines[] = 'Bet time: '.date('m/d/y g:i A', strtotime($bet->insert_at));
		$lines[] = 'Last match: '.date('m/d/y g:i A', strtotime($bet->expire_at));

		$results = json_decode($bet->results, false);		
		foreach($results as $result)
		{
			$lines[] = $result->fixture->qbet.' Sign: '.$result->option->name.'('.$result->option->group.') '.number_format($result->prize, 2);
		}

		$bonus = 0;
        if($bet->type=='Direct')
          $bonus = $bet->bonus_percent * $bet->max_win / 100;

		$total_win = 0;
		$status = '';
        if($bet->bet_result=='none')
        {
          $total_win = $bet->max_win + $bonus;
          $status = "inProgress";
        }
        else if($bet->bet_result=='lost')
        {
          $total_win = 0;
          $status = "Lost";
        }
        else if($bet->bet_result=='won')
        {
          $total_win = 0;
          $status = "Won";
		}

		$lines[] = 'Status:    '.$status;
		$lines[] = 'Type: '.$bet->type.'   Under: '.$bet->under;
		$lines[] = 'Amount: # '.number_format($bet->amount, 0);
		if($bet->type=='Direct')
			$lines[] = 'Pos.Win: # '.number_format($bet->max_win, 2);
		else
			$lines[] = 'Max Win: # '.number_format($bet->max_win, 2);

		$lines[] = 'Bonus:    # '.number_format($bonus, 2);
		$lines[] = 'Total Win: # '.number_format($total_win, 2);
		$lines[] = "\n\n\n";
		return implode("\n", $lines);
	}	

	public function format_bet($user, $bet)
	{
		if($bet->event_count <= 15)
			return $this->format_bet_full($user, $bet);
		return $this->format_bet_short($user, $bet);
	}


	// public function format_bet($user, $bet)
	// {
	// 	$lines  = [];
	// 	$lines[] = '             BetTwoStar';
	// 	$lines[] = 'Player: '. $user->login.' ( '.$user->surname. ' '.$user->name.' )';
	// 	$results = json_decode($bet->results, false);		
	// 	foreach($results as $result)
	// 	{
	// 		$lines[] = $this->line_spliter;
	// 		$templn = strval($result->fixture->qbet). '  '.$result->fixture->home_team.'  '.$result->fixture->away_team;
	// 		$width = $this->calc_line_width($templn);
	// 		if($width <= $this->line_width)
	// 			$lines[] = $templn;
	// 		else
	// 		{
	// 			$nlns = $this->make_columned_contents(
	// 				[strval($result->fixture->qbet), 
	// 				$result->fixture->home_team, 
	// 				$result->fixture->away_team], 
	// 				[40, 62, 62], '  ');	
	// 			foreach($nlns as $line)$lines[] = $line;
	// 		}
	// 		$lines[] = 'Sign: '.$result->option->name.'('.$result->option->group.') '.number_format($result->prize, 2);
	// 		$lines[] = date('m/d/y g:i A', strtotime($result->fixture->datetime));
	// 	}
				
	// 	$lines[] = $this->line_spliter;
	// 	$nlns = $this->make_columned_contents(	['N events', count($results)],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['BetID', $bet->bet_id],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Date', date('m/d/y g:i A', strtotime($bet->insert_at))],	[56, 110], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Type', $bet->type],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Week', $bet->week.'('.$bet->year.')'],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Amount', '# '.number_format($bet->amount, 0)],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Max Win', '# '.number_format($bet->max_win,2)],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Win', '# '.number_format($bet->won_amount,2)],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Bonus', $bet->bonus_percent.' %', ],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Bonus',  '# '.$bet->bonus],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;

	// 	$nlns = $this->make_columned_contents(	['Total win', '# '.number_format($bet->total_win, 2)],	[56, 100], '  ');
	// 	foreach($nlns as $line)$lines[] = $line;
	// 	return implode("\n", $lines);
	// }	



}


