<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ResultGoal_model extends CI_Model {
	public function __construct()
	{
		parent::__construct();
	}

	public function is_win($opt, $home, $away)
	{
		$res = false;
		switch($opt)
		{
			case 'odd_1':
				$res = $home > $away;
			break;
			case 'odd_x':
				$res = $home == $away;
			break;
			case 'odd_2':
				$res = $home < $away;
			break;
			case 'odd_1x':
				$res = $home >= $away;
			break;
			case 'odd_12':
				$res = $home > $away || $home < $away;
			break;
			case 'odd_x2':
				$res = $home <= $away;
			break;
			case 'o+0.5':
				$res = ($home + $away) >= 1;
			break;
			case 'u+0.5':
				$res = ($home + $away) == 0;
			break;
			case 'o+1.5':
				$res = ($home + $away) >= 2;
			break;
			case 'u+1.5':
				$res = ($home + $away) <= 1;
			break;
			case 'o+2.5':
				$res = ($home + $away) >= 3;
			break;
			case 'u+2.5':
				$res = ($home + $away) <= 2;
			break;
			case 'o+3.5':
				$res = ($home + $away) >= 4;
			break;
			case 'u+3.5':
				$res = ($home + $away) <= 3;
			break;
			case 'o+4.5':
				$res = ($home + $away) >= 5;
			break;
			case 'u+4.5':
				$res = ($home + $away) <= 4;
			break;
			case 'o+5.5':
				$res = ($home + $away) >= 6;
			break;
			case 'u+5.5':
				$res = ($home + $away) <= 5;
			break;
			case 'bts_yes':
				$res = ($home > 0 &&  $away > 0);
			break;
			case 'bts_no':
				$res = ($home == 0 ||  $away == 0);
			break;
		}
		return $res;
	}

}


