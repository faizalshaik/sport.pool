<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Accept: application/json');
header('Content-Type: application/json');


class Calc_api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Africa/Lagos');
		$this->load->model('User_model');
		$this->load->model('Base_model');
		$this->load->model('Calc_model');
		$this->load->model('FormatEvent_model');
	}

	public function test()
	{
		$gamelist = [
			['key'=>1, 'state'=>'win', 'prize'=> 1.2],
			['key'=>1, 'state'=>'win', 'prize'=> 1.12],
			['key'=>1, 'state'=>'win', 'prize'=> 2.95]
		];

		// $under = count($gamelist);
		$under = 2;
		$amount = 120;
		echo $this->Calc_model->calc_win($gamelist, $under, $amount, true);
	}	
}
