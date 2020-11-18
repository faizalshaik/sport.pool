<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include("assets/global/admin.global.php");
class Cms extends CI_Controller {

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

	public function index()
	{
		$user =  $this->logon_check();
		if($user!=null) {
			redirect('Cms/dashboard/', 'refresh');
		} 
	}
	public function login(){
		$this->load->view("admin/login");
	}
	public function logout(){
		$this->session->sess_destroy();
		redirect('Cms/', 'refresh');
	}
	public function auth_user() {
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$conAry = array('email' => $email);
		$ret = $this->Base_model->getRow('tbl_admin', $conAry);
		if(!empty($ret)){       
			if (password_verify($password, $ret->password)) {       
				$sess_data = array('user_id'=>$ret->Id, 'is_login'=>true, 'user'=>$ret);
				$this->session->set_userdata($sess_data);
				redirect('Cms/dashboard/', 'refresh');
			}
		}
		
		$this->session->set_flashdata('messagePr', 'Incorrect email or password.');
		redirect( 'Cms/login', 'refresh');
	}
	public function dashboard() {
		$user =  $this->logon_check();
		if($user!=null)
		{
			$param['uri'] = '';
			$param['kind'] = '';

			$this->load->view("admin/include/header", $param);	
			$data['user_cnt'] = $this->User_model->getCounts(null);			
			$data['prayers_cnt'] = $this->Base_model->getCounts('tbl_prayer', null);
			$data['full_grown_cnt'] = $this->Base_model->getCounts('tbl_prayer',['step'=>6]);			
			$data['in_growing_cnt'] = $data['prayers_cnt'] - $data['full_grown_cnt'];

			$this->load->view("admin/dashboard", $data);
		}
	}

	public function updateAccount() {
		$user =  $this->logon_check();
		if($user!=null){
			$email = $this->input->post('email');
			$password = $this->input->post('password');
			$id = $this->session->userdata('user_id');
			$npass = password_hash($password, PASSWORD_DEFAULT);
			$updateAry = array('email'=>$email,
				'password'=>$npass,
				'modified'=>date('Y-m-d'));
			$ret = $this->Base_model->updateData('tbl_admin', array('Id'=>$id), $updateAry);
			if($ret > 0) 
				$this->session->set_flashdata('messagePr', 'Update Account Successfully..');
			else
				$this->session->set_flashdata('messagePr', 'Unable to Update Account..');
			redirect('Cms/dashboard/', 'refresh');
		}
	}

	public function users(){
		$user =  $this->logon_check();
		if($user==null) return;

		$param['uri'] = '';
		$param['kind'] = 'table';
		$this->load->view("admin/include/header", $param);	
		$this->load->view("admin/view_user", $param);
	}

	public function videos(){
		$user =  $this->logon_check();
		if($user==null) return;

		$param['uri'] = '';
		$param['kind'] = 'table';
		$this->load->view("admin/include/header", $param);	
		$this->load->view("admin/view_video", $param);
	}	

}
