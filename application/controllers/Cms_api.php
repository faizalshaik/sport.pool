<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cms_api extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Africa/Lagos');
				
		$this->load->model('User_model');
		$this->load->model('Base_model');
	}

	public function logon_check()
	{
		$is_login = $this->session->userdata('is_login');
		$userId = $this->session->userdata('user_id');
		if( isset($is_login) && $is_login ==TRUE && isset($userId) && $userId >0){
			return TRUE;
		} else {
			redirect('Cms/login', 'refresh');
			return FALSE;
		}
	}	

	public function getDataById() {
		$this->logon_check();

		$Id = $this->input->post("Id");
		$tableName = $this->input->post("tbl_Name");
		$ret = $this->Base_model->getRow($tableName, array('Id'=>$Id));
		echo json_encode($ret);
	}

	public function delData() {
		$this->logon_check();
		$Id = $this->input->post("Id");
		$tableName = $this->input->post("tbl_Name");
		$this->Base_model->deleteRow($tableName, array('Id'=>$Id));
		echo json_encode("1");
	}
	

	public function get_users()
	{
		$this->logon_check();
		$data = array();
		$rows = $this->User_model->getDatas(null, 'created_at');
		foreach($rows as $item)
		{
			$row = [];
			$row[] =  $item->Id;
			$row[] =  $item->name;
			$row[] =  $item->email;
			$row[] =  $item->created_at;
			//$row[] =  $item->logined_at;

			$totalPrayer = $this->Base_model->getCounts('tbl_prayer', ['user_id'=>$item->Id]);
			$fullGrown = $this->Base_model->getCounts('tbl_prayer', ['user_id'=>$item->Id, 'step'=>6]);
			$row[] = $totalPrayer;
			$row[] = $fullGrown;
			$row[] = $totalPrayer - $fullGrown;
			$data[]=$row;
		}

		$output = array(
			"draw" => "",
			"recordsTotal" => count($data),
			"recordsFiltered" => count($data),
			"data" => $data
		);
		echo json_encode($output);
	}

	public function get_videos()
	{
		$this->logon_check();
		$data = array();
		$rows = $this->Base_model->getDatas('tbl_video',null);
		foreach($rows as $item)
		{
			$row = [];
			$row[] =  $item->Id;
			$row[] =  $item->link;

			$strAction = '<a href="javascript:void(0)" class="on-default edit-row" ' .
				'onclick="onEdit(' . $item->Id . ')" title="Edit" ><i class="fa fa-pencil text-info m-r-5"></i></a>' .
				'<a href="javascript:void(0)" class="on-default remove-row" ' .
				'onclick="onDelete(' . $item->Id . ')" title="Remove" ><i class="fa fa-trash-o text-danger"></i></a>';
			$row[] =  $strAction;
			$data[]=$row;
		}

		$output = array(
			"draw" => "",
			"recordsTotal" => count($data),
			"recordsFiltered" => count($data),
			"data" => $data
		);
		echo json_encode($output);
	}

	public function save_video()
	{
		$this->logon_check();
		$id = $this->input->post("Id");
		$link = $this->input->post("link");

		if($id=='' || $id==0)
		{
			$this->Base_model->insertData('tbl_video', ['link'=>$link]);
		}
		else
		{
			$this->Base_model->updateData('tbl_video', ['Id'=>$id], ['link'=>$link]);
		}
		echo json_encode("1");
	}

	

}