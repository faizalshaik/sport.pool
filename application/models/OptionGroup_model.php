<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class OptionGroup_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Base_model');
	}

	private function find_data($list, $id)
	{
		foreach($list as $ele)
		{
			if($ele->id == $id) return $ele;
		}
		return null;
	}
		
	public function get_option_groups()
	{
		$options = $this->Base_model->getDatas('tbl_option', null, 'id');
		$groups = $this->Base_model->getDatas('tbl_group', null, 'id');
		$rows = $this->Base_model->getDatas('tbl_option_group', null, 'option_id');

		$result = [];
		foreach($rows as $row)
		{
			$grp = null;
			if(isset($result[$row->group_id]))
				$grp = $result[$row->group_id];
			else
			{
				$g = $this->find_data($groups, $row->group_id);
				$grp = ['name'=> $g->name, 'short_name'=>$g->short_name, 'id'=>$g->id, 'options'=>[], 'max_odd'=>$g->max_odd, 'odd_increase'=>$g->odd_increase];
			}

			$opts = $grp['options'];
			$opt = $this->find_data($options, $row->option_id);
			$opts[] = ['name'=> $opt->name, 'key'=> $opt->key, 'id' => $opt->id];
			$grp['options'] = $opts;
			$result[$row->group_id] = $grp;			
		}

		$resData = [];
		foreach($result as $key => $grp)
			$resData[] = $grp;
		return $resData;		
	}	
}


