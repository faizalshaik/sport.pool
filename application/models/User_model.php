<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class User_model extends CI_Model {

	var $order = array('id' => 'asc'); // default order 
	public function __construct()
	{
		 parent::__construct();
		 $this->tblName = "tbl_user";
	}

	public function doChangePwd($Id, $userPWD, $newPWD)
	{
		global $MYSQL;
		$strsql = sprintf("select * from %s where Id='$Id' ", $MYSQL['_adminDB']);
		$ret = $this->db->query($strsql)->row();
		if($ret){
			$salt = $ret->userPWDKey;
			$genPWD = crypt($userPWD, $salt);
			if($genPWD == $ret->userPWD){
				$salt = md5(date("YmdHis"));
				$genPWD = crypt($newPWD, $salt);
				$strsql = sprintf("UPDATE %s SET userPWD='$genPWD', userPWDKey='$salt' WHERE Id= '$Id' ", 
					$MYSQL['_adminDB']);
				$this->db->query($strsql);
				return TRUE;
			}
		}
		return FALSE;
	}
	private function _get_datatables_query($conAry, $srchAry, $orderAry, $kind='', $select='') {
		global $MYSQL;
		if($select !='') {
			$this->db->select($select);
		}
        $this->db->from($this->tblName);
		if($kind =='report') {
			$this->db->join($MYSQL['_jobDB'].' b', 'a.job_id = b.Id', 'left');
			$this->db->join($MYSQL['_userDB'].' c', 'a.user_id = c.Id', 'left');
		} else if($kind =='user') {
			$this->db->join($MYSQL['_companyDB'].' b', 'a.company_id = b.Id', 'left');
		} else if($kind =='job') {
			$this->db->join($MYSQL['_subjectDB'].' b', 'a.subject_id = b.Id', 'left');
		}
		if(!empty($conAry))
			$this->db->where( $conAry );
        $i = 0;
        foreach ($srchAry as $item) // loop column
        {
            if($_POST['search']['value']) // if datatable send POST for search
            {
                if($i===0) // first loop
                {
                    $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
                    $this->db->like($item, $_POST['search']['value']);
                }
                else
                {
                    $this->db->or_like($item, $_POST['search']['value']);
                }
 
                if(count($srchAry) - 1 == $i) //last loop
                    $this->db->group_end(); //close bracket
            }
            $i++;
        }
         
        if(isset($_POST['order'])) // here order processing
        {
            $this->db->order_by($orderAry[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } 
        else if(isset($this->order))
        {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    public function count_filtered($conAry, $srchAry, $orderAry, $kind='', $select='') {
        $this->_get_datatables_query($conAry, $srchAry, $orderAry, $kind, $select);
        $query = $this->db->get();
        return $query->num_rows();
    }

	public function getDatas( $conAry, $orderBy='' ) {
		$this->db->from($this->tblName);
		if(!empty($conAry))
			$this->db->where( $conAry );
		if($orderBy !='') {
			$this->db->order_by($orderBy, 'ASC');
		}
		$ret = $this->db->get()->result();
		return $ret;
	}
	public function updateDataAry($field, $conAry, $updateAry) {
		if(!empty($updateAry)) {
			$this->db->where_in($field, $conAry);
			$this->db->update($this->tblName, $updateAry);
		}
		return $this->db->affected_rows();
	}

	public function updateData($conAry, $updateAry) {
		if(!empty($updateAry)) {
			$this->db->update($this->tblName, $updateAry, $conAry);
		}
		return $this->db->affected_rows();
	}
	public function updateOnOff($Id, $field)
    {
		global $MYSQL;
		$this->db->from($this->tblName);
		$this->db->set($field, '1-'.$field, FALSE);
		$this->db->where('Id', $Id);
		$this->db->update();
    }
	public function deleteByField($field, $value ) {
		$this->db->where($field, $value);
        $this->db->delete($this->tblName);
	}

	public function deleteRows( $field, $delAry ) {
		if(!empty($delAry)) {
			$this->db->where_in($field, $delAry);
			$this->db->delete($this->tblName);
		}
		return $this->db->affected_rows();
	}

	public function getTableDatas($conAry, $srchAry, $orderAry, $kind='', $select='') {
		$this->_get_datatables_query($conAry, $srchAry, $orderAry, $kind, $select);
        if($_POST['length'] != -1)
        	$this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
	}
	public function getCounts($conAry) {
    	$this->db->from($this->tblName);
		if(!empty($conAry))
			$this->db->where( $conAry );
		return $this->db->count_all_results();
    }
    
    public function insertData($data)
    {
        $this->db->insert($this->tblName, $data);
        return $this->db->insert_id();
    }
    public function getDataById($Id)
    {
        $this->db->from($this->tblName);
        $this->db->where('Id',$Id);
        $query = $this->db->get();
        return $query->row();
    }
    public function getRow($conAry) {
    	$this->db->from($this->tblName);
    	$this->db->where($conAry);
        $query = $this->db->get();
        return $query->row();
    }
    public function setField($field, $value, $conAry, $valueString=FALSE) {
    	$this->db->from($this->tblName);
		$this->db->set($field, $value, $valueString);
		$this->db->where($conAry);
		$this->db->update();
	}
	
	public function getUserIdByToken($token)
	{
		$this->db->from($this->tblName);
		$this->db->where('token', $token);
		$ret = $this->db->get()->result();		
		if(!empty($ret)){
			return $ret[0]->Id;
		}
		return 0;
	}

	public function getOnlineUsers()
	{
        $tdate = date("Y-m-d H:i:s", time() - 600);
		$strQuery = "select * from ".$this->tblName." where logined_at >'" .$tdate."'";
        return $this->db->query($strQuery)->result();
	}
	
}


