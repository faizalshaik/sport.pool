<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	public function getDatas($tblName, $conAry, $orderBy='', $asc='ASC', $start = -1, $limit = -1)
	{
		$this->db->from($tblName);
		if(!empty($conAry))
			$this->db->where( $conAry );

		if(is_array($orderBy))
		{
			foreach($orderBy as $col=>$a)
				$this->db->order_by($col, $a);
		}
		else if($orderBy !='') {
			$this->db->order_by($orderBy, $asc);
		}
		if($start >=0 && $limit >=0) {
			$this->db->limit($limit, $start);
		}
		$ret = $this->db->get()->result();
		return $ret;
	}

	public function updateData($tblName, $conAry, $updateAry) 
	{
		if(!empty($updateAry)) {
			$this->db->update($tblName, $updateAry, $conAry);
		}
		return $this->db->affected_rows();
	}

	public function deleteRow($tblName,  $conArry ) 
	{
		if(!empty($conArry)) {
			$this->db->where($conArry);
			$this->db->delete($tblName);
		}
	}	

	public function deleteByField($tblName, $field, $value ) {
		$this->db->where($field, $value);
        $this->db->delete($tblName);
	}

	public function getCounts($tblName, $conAry) {
    	$this->db->from($tblName);
		if(!empty($conAry))
			$this->db->where( $conAry );
		return $this->db->count_all_results();
    }

    public function insertData($tblName, $data)
    {
        $this->db->insert($tblName, $data);
        return $this->db->insert_id();
    }

	public function getRow($tblName, $conAry) 
	{
    	$this->db->from($tblName);
    	$this->db->where($conAry);
        $query = $this->db->get();
        return $query->row();
    }

    public function setField($tblName, $field, $value, $conAry, $valueString=FALSE) {
    	$this->db->from($tblName);
		$this->db->set($field, $value, $valueString);
		$this->db->where($conAry);
		$this->db->update();
    }
    public function getDataById($tblName, $Id)
    {
        $this->db->from($tblName);
        $this->db->where('Id',$Id);
        $query = $this->db->get();
        return $query->row();
	}

	public function getMaxId($tblName)	
	{
		$maxid = 0;
		$row = $this->db->query('SELECT MAX(Id) AS `maxid` FROM `'.$tblName.'`')->row();		
		if ($row) {
			$maxid = $row->maxid; 
		}
		return $maxid;
	}
	public function truncate($tblName)
	{
		$this->db->from($tblName);
		$this->db->truncate();		
	}	

	public function updateBatch($table, $updateArry, $key)
	{
		$this->db->update_batch($table,$updateArry, $key);
	}	

	public function insertBatch($table, $updateAry)
	{
		$this->db->insert_batch($table, $updateAry);
	}
	
	public function get_max_value($table, $col, $conAry=null)
	{
		// $this->db->select_max('`'.$col.'` as max_val');
		$this->db->select_max($col);
		if($conAry)
			$this->db->where($conAry);
		$result = $this->db->get($table)->row();


		if($result)
		{
			$res = json_decode(json_encode($result), true);
			return $res[$col];
		}			
		return 0;
	}

	public function get_min_value($table, $col, $conAry=null)
	{
		// $this->db->select_max('`'.$col.'` as max_val');
		$this->db->select_min($col);
		if($conAry)
			$this->db->where($conAry);

		$result = $this->db->get($table)->row();
		if($result)
		{
			$res = json_decode(json_encode($result), true);
			return $res[$col];
		}			
		return 0;
	}

	
}


