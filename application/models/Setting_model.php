<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends CI_Model {
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Base_model');		
	}

	public function settings()
	{
		$rows = $this->Base_model->getDatas('tbl_setting', null);

		$settings = [];
		foreach($rows as $row)
		{
			$settings[$row->name] = $row->value;
		}
		return $settings;
	}

}


