<?php

class Brainmaps extends Controller {

	private $data;
	
	private $table_id = 4;

	function Brainmaps()
	{
		parent::Controller();

		$this->load->database('default');
		$this->load->helper('form');
		$this->load->library('session');
		$this->load->library('journal');
		
		$this->load->helper('login');
		require_login();
		
		$this->load->model('Leftmenu','lmenu',TRUE);
		
		if ($this->lmenu->all_number() > 0) {
				
			$this->data['leftMenu'] = $this->lmenu->get_all();
				
		}
		
		
	}

	function index() {

		$this->data['action'] = 'index';
		$this->load->view('brainmaps_view',$this->data);


	}

	function add(){


		$id = $this->input->get('id');
		
		if(!empty($id)) {
			
			$this->load->model('Literature_model','literM',TRUE);
			
			if (($qida = $this->literM->get_one($id)) != FALSE){
				
				$this->data['literature_data'] = $qida->row();
				
			}
			
			
			
		}
		
		$this->data['fields'] = $this->db->field_data('brain_maps');

		$this->data['action'] = 'add';

		$this->data['extraHeader'] = '<script type="text/javascript" src="js/autocomplete.js"></script>';
		$this->data['extraHeader'] .= '<script type="text/javascript" src="js/brainmaps.js"></script>';
		
// 		print_r($this->data);

		$pid = $this->session->userdata('pub_id');
		if (!empty($pid)){
			$this->data['lit_id'] = $pid;
		}  
			
		$this->load->view('brainmaps_view',$this->data);


	}

	function insert() {

		$fields = $_POST;
		
		

		$lit_id = $fields['literature_id'];
		
		
		$this->db->select('literature_index');
			
		$qida = $this->db->get_where('literature',array('literature_id' => $lit_id));
		
			
						
		$rowa = $qida->row();
		
		

		$fields['brain_maps_index'] = $rowa->literature_index;

		$fields['brain_maps_type'] = "ff";
		
		if (isset($fields['delin'])) {	
			$fields['brain_maps_type'][0] = "t"; 
		}
		
		if (isset($fields['adop'])) {
			$fields['brain_maps_type'][1] = "t";
		}
		
		unset ($fields['delin']);
		unset ($fields['adop']);
		
		
		$qida = $this->db->get_where('brain_maps',array('literature_id' => $lit_id));
		
		if ($qida->num_rows()){
			
			$result = '{"result":"0","message":"An error has occured: this literature already has brain map"}';
			
			echo $result;
			
			return false;
			
			
			
		}
		
		
			
		if ($this->db->insert('brain_maps',$fields) === FALSE){
			$this->data['add_message'] = "An error has occured";
			
			$result = '{"result":"0","message":"An error has occured!"}';
				
		} else {
				
			
			$lid = $this->db->insert_id();
			$this->journal->newrecord($this->session->userdata('user_id'),4,$lid);
				
				
			$result = '{"result":"1","message":"Succes!", "newurl":"index.php?c=brainmaps&m=edit&id='.$lid.'"}';
		}

		echo $result;


	}
	
	function edit() {
		
		$lid = $this->input->get('id');
		
		$this->data['action'] = 'edit';
		
		$this->data['block_message'] = "Nothing was sent";
		
		if (!empty($lid)){
			
			
			$qida = $this->db->get_where('brain_maps',array('brain_maps_id' => $lid));
			
			$this->data['block_message'] = "Nothing was found";
			
			if ($qida->num_rows() > 0) {
			
				unset($this->data['block_message']);
				
				
				$this->data['extraHeader'] = '<script type="text/javascript" src="js/autocomplete.js"></script>';
				$this->data['extraHeader'] .= '<script type="text/javascript" src="js/brainmaps.js"></script>';
				
				
				$this->data['fields'] = $this->db->field_data('brain_maps');
				
				$this->data['map_data'] = $qida->row();
				
				$this->db->select('literature_title, literature_year, literature_id');
				  
				$qidb = $this->db->get_where('literature',array('literature_id' => $this->data['map_data']->literature_id));
				
				$this->data['lit_data'] = $qidb->row();
				
				
				// collecting number of brain sites for this bmap
				
				$this->db->where('brain_maps_id',$lid)->from('brain_sites');
				$number = $this->db->count_all_results();
				
				if ($number > 0) {
					
					$this->data['bsites_number'] = $number;
					
				}
				
				
				
			
			} 
			
		} 
		
		
		$this->load->view('brainmaps_view',$this->data);
		
		
		
		
	}
	
	
	
	function update() {
		
		
		
		$fields = $_POST;
		
		$id = $this->input->get('id');
		
		// collecting previously saved data for journal
		
		$prev_data = $this->db->get_where('brain_maps',array('brain_maps_id' => $id));
		
		$prev_data = $prev_data->row();
		
		
		$fields['brain_maps_type'] = "ff";
		
		if (isset($fields['delin'])) {
			$fields['brain_maps_type'][0] = "t";
		}
		
		if (isset($fields['adop'])) {
			$fields['brain_maps_type'][1] = "t";
		}
		
		unset ($fields['delin']);
		unset ($fields['adop']);
		
		
		// finaly updating...
		
		$this->db->where('brain_maps_id',$id);
		
		if ($this->db->update('brain_maps',$fields) === FALSE){
				
			$result = '{"result":"0","message":"An error has occured!"}';
				
		} else {
				
				
				
			//newrecord (USER_ID,TABLE_ID,ENTRY_ID,ACTION_ID(1=ins,2=update,3=del)=1,DATA='');
				
			$this->journal->newrecord($this->session->userdata('user_id'),$this->table_id,$id,2,$prev_data);
				
				
			$result = '{"result":"1","message":"Succes!"}';
				
		}
		
		
		echo $result;
		
		
		
		
	}
	
	function ajaxAtocomplit() {
		
		
		$qr = $this->input->post('query');
		
		$result = 'no thing';
		
		if (!empty($qr)) {
		
		
		
			$qida = $this->db->query("SELECT DISTINCT bm.brain_maps_id as bmid,lt.literature_index as litind FROM literature lt JOIN brain_maps bm ON (lt.literature_id=bm.literature_id) WHERE literature_index LIKE ? LIMIT 7", array($qr . '%'));
		
			if ($qida->num_rows() > 0) {
		
				$result = "{ query:'" . $qr . "', suggestions:[";
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->litind ."',";
				}
		
				$result = substr($result, 0, strlen($result) - 1);
		
				$result .="],data:[";
		
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->bmid ."',";
				}
		
				$result = substr($result, 0, strlen($result) - 1);
					
					
				$result .="]}";
			}
		}
		
		echo $result;
		
		
		
	}
}

?>
