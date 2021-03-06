<?php

class Brainsites extends Controller {

	private $data;

	function Brainsites()
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
		$this->load->view('brainsites_view',$this->data);


	}

	function add(){


		$this->data['fields'] = $this->db->field_data('brain_sites');

		$this->data['action'] = 'add';
		
		$qida = $this->db->get('brain_sites_types');
		
		$this->data['type_options'] = array();
		
		$temp_arr = array();
		
		foreach ($qida->result() as $rowa) {
			
			$temp_arr[$rowa->brain_sites_type_id] = $rowa->brain_sites_type_name;
			
		}
		
		$this->data['type_options'] = $temp_arr;
		
		
		$qida = $this->db->get('brain_sites_classes');
		
		$this->data['classes_options'] = array();
		
		foreach ($qida->result() as $rowa) {
				
			$this->data['classes_options'][$rowa->brain_sites_classes_id] = $rowa->brain_sites_classes_name;
				
		}
		
		
		$bmapid = $this->session->userdata('bmap_id');
		
		if (!empty($bmapid)) {
			
			$this->db->select('literature_id, literature_title, literature_index');
			$qida = $this->db->get_where('literature',array('literature_id'=>$bmapid));
			$this->data['bmap_data'] = $qida->row();
			
			
			
			
		}
		
		$bmapid = $this->input->get('id');
		
		if (!empty($bmapid)) {
				
			$this->load->model('Literature_model','literM',TRUE);
			
			if (($qida = $this->literM->get_one($bmapid)) != FALSE){
				
					$this->data['bmap_data'] = $qida->row();
				
			}
			
							
				
		}
		
		$bmapid = $this->input->get('mid');
		
		if (!empty($bmapid)) {
		
			$this->load->model('Literature_model','literM',TRUE);
				
			echo "loaded and not empty";
				
			if (($qida = $this->literM->get_from_map($bmapid)) != FALSE){
		
				$this->data['bmap_data'] = $qida->row();
		
			}
				
				
		
		}
		
		
		
		
		
		

		$this->data['extraHeader'] = '<script type="text/javascript" src="js/autocomplete.js"></script>';
		$this->data['extraHeader'] .= '<script type="text/javascript" src="js/brainsites.js"></script>';

		// 		print_r($this->data);
			
		$this->load->view('brainsites_view',$this->data);


	}

	function insert() {

		$fields = $_POST;
		
		$literature_id = $fields['brain_maps_id'];
		
		// getting brain_maps_id using literature id  
		
		$qida = $this->db->get_where('brain_maps',array('literature_id' =>$fields['brain_maps_id']));
		
		if ($qida->num_rows() > 0) {
			
			$rowa = $qida->row();
			
			$fields['brain_maps_id'] = $rowa->brain_maps_id;
			
			
		} else {
			
			//no brain map was found? inserting new ...
			
			
			$this->db->select('literature_index');
				
			$qida = $this->db->get_where('literature',array('literature_id' => $fields['brain_maps_id']));
			

			$rowa = $qida->row();
			
			$fields2 = array();
			
			$fields2['literature_id'] = $fields['brain_maps_id'];
			
			$fields2['brain_maps_index'] = $rowa->literature_index;
			
			$fields2['brain_maps_type'] = "ff";
			
			$this->db->insert('brain_maps',$fields2);
			
			// saving to a journal this event
			
			$lid = $this->db->insert_id();
			
			$this->journal->newrecord($this->session->userdata('user_id'),4,$lid);
			
			// using bmapid ..
			$fields['brain_maps_id'] = $lid;
			
			
			
			
		}
		
		
		
		// the same brain site in the same bmap ? impossible! 
		$qida = $this->db->get_where('brain_sites',array('brain_sites_acronyms_id'=>$fields['brain_sites_acronyms_id'],'brain_maps_id' =>$fields['brain_maps_id']));
		
		if ($qida->num_rows() > 0) {
		
		
			$result = '{"result":"0","message":"An error has occured: You are trying to add already exitsting data!"}';
			
			echo $result;
		
			return false;
		}
		
		
		//proceding as usual 
		
		//generating index of bsite 
		
		$fields['brain_sites_index'] = '';
		
		$this->db->select('brain_maps_index');
		$qida = $this->db->get_where('brain_maps',array('brain_maps_id'=>$fields['brain_maps_id']));
		$rowa = $qida->row();
		
		$fields['brain_sites_index'] .= $rowa->brain_maps_index;
		
		$this->db->select('acronym_name');
		$qida = $this->db->get_where('brain_site_acronyms',array('brain_site_acronyms_id'=>$fields['brain_sites_acronyms_id']));
		$rowa = $qida->row();
		
		$fields['brain_sites_index'] .="_".$rowa->acronym_name;
			
		//inserting 
		
		if ($this->db->insert('brain_sites',$fields) === FALSE){
							
			$result = '{"result":"0","message":"An error has occured!"}';

		} else {

			$lid = $this->db->insert_id();
			$this->journal->newrecord($this->session->userdata('user_id'),6,$lid);
				

			$this->session->set_userdata('bmap_id',$literature_id);
			
			$result = '{"result":"1","message":"Succes!","newurl":"index.php?c=brainmaps&m=edit&id='.$fields['brain_maps_id'].'"}';
			
			
		}
		
		echo $result;

	}
	
	
	function edit() {
		
		
		$lid = $this->input->get('id');
		
		$this->data['action'] = 'edit';
		
		$this->data['block_message'] = "Nothing was sent";
		
		if (!empty($lid)){
		
		
			$qida = $this->db->get_where('brain_sites',array('brain_sites_id' => $lid));
			
			$this->data['block_message'] = "Nothing was found";
		
			if ($qida->num_rows() > 0) {
					
				unset($this->data['block_message']);
				
				$this->data['extraHeader'] = '<script type="text/javascript" src="js/autocomplete.js"></script>';
				$this->data['extraHeader'] .= '<script type="text/javascript" src="js/brainsites.js"></script>';
		
		
				$this->data['fields'] = $this->db->field_data('brain_sites');
		
				$this->data['bs_data'] = $qida->row();
				
				//coll bmap 
				
				$this->db->join('literature','literature.literature_id = brain_maps.literature_id');
				
				$this->db->select('literature_index, literature_title, brain_maps.*');
				
				$qida = $this->db->get_where('brain_maps',array('brain_maps_id' => $this->data['bs_data']->brain_maps_id));
				
				
				$rowa = $qida->row();
				
				$this->data['bmap_data'] = $qida->row();
				
				
				// collecting types and classes from db
				
				$qida = $this->db->get('brain_sites_types');
				
				$this->data['type_options'] = array();
				
				foreach ($qida->result() as $rowa) {
				
					$this->data['type_options'][$rowa->brain_sites_type_id] = $rowa->brain_sites_type_name;
				
				}
				
				/*
				$qida = $this->db->get('brain_sites_classes');
				
				$this->data['classes_options'] = array();
				
				foreach ($qida->result() as $rowa) {
				
					$this->data['classes_options'][$rowa->brain_sites_classes_id] = $rowa->brain_sites_classes_name;
				
				}
				*/
				
				// acronyms
				
				$qida = $this->db->get_where('brain_site_acronyms',array('brain_site_acronyms_id' => $this->data['bs_data']->brain_sites_acronyms_id));
				
				$this->data['acr_data'] = $qida->row();
				
				
				$this->load->view('brainsites_view',$this->data);
		

			}
		
		}
		
		
	}
	
	
	
	function update() {
	
		$fields = $_POST;
	

		/*
		$qida = $this->db->get_where('brain_sites',array('brain_sites_acronyms_id'=>$fields['brain_sites_acronyms_id'],'brain_maps_id' =>$fields['brain_maps_id']));
		
		if ($qida->num_rows() > 0) {
		
		
			$this->data['add_message'] = "An error has occured: You are trying to add already exitsting data";
		
			$this->edit();
		
			return false;
		}
		
		*/
		
		
		
		
		
		$qida = $this->db->get_where('brain_maps',array('literature_id' =>$fields['brain_maps_id']));
		
		if ($qida->num_rows() > 0) {
				
			$rowa = $qida->row();
				
			$fields['brain_maps_id'] = $rowa->brain_maps_id;
				
				
		} else {
				
			//no brain map was found? inserting new ...
				
				
			$this->db->select('literature_index');
		
			$qida = $this->db->get_where('literature',array('literature_id' => $fields['brain_maps_id']));
				
		
			$rowa = $qida->row();
				
			$fields2 = array();
				
			$fields2['literature_id'] = $fields['brain_maps_id'];
				
			$fields2['brain_maps_index'] = $rowa->literature_index;
				
			$fields2['brain_maps_type'] = "ff";
				
			$this->db->insert('brain_maps',$fields2);
				
			// saving to a journal this event
				
			$lid = $this->db->insert_id();
				
			$this->journal->newrecord($this->session->userdata('user_id'),4,$lid);
				
			// using bmapid ..
			$fields['brain_maps_id'] = $lid;
				
				
				
				
		}
		
		
		
		
		// creating index of this bsite using bmap and acronym table
		
		
		$fields['brain_sites_index'] = '';
	
		$this->db->select('brain_maps_index');
		$qida = $this->db->get_where('brain_maps',array('brain_maps_id'=>$fields['brain_maps_id']));
		$rowa = $qida->row();
	
		$fields['brain_sites_index'] .= $rowa->brain_maps_index;
	
		$this->db->select('acronym_name');
		$qida = $this->db->get_where('brain_site_acronyms',array('brain_site_acronyms_id'=>$fields['brain_sites_acronyms_id']));
		$rowa = $qida->row();
	
		$fields['brain_sites_index'] .="_".$rowa->acronym_name;
		
		//echo $fields['brain_sites_index'];
		
		//kk
		
		$bs_id = $this->input->get('bsid');
		
		$this->db->where('brain_sites_id',$bs_id);
		
		if ($this->db->update('brain_sites',$fields) === FALSE){
			
	
			$result = '{"result":"0","message":"An error has occured!"}';
	
		} else {		

			$this->journal->newrecord($this->session->userdata('user_id'),6,$bs_id,2);
		
			$result = '{"result":"1","message":"Succes!","newurl":"index.php?c=brainmaps&m=edit&id='.$fields['brain_maps_id'].'"}';
		}
		
		echo $result;
		
	
	}
	
	
	function ajaxAtocomplit(){
		
		$qr = $this->input->post('query');
		
		$pid = $this->session->userdata('pub_id');
		
		$result = 'no thing';
		
		if ((!empty($qr)) && (!empty($pid)))  {
			
			$qida = $this->db->query("SELECT brain_maps_id as bmid FROM brain_maps WHERE literature_id = ?",array($pid));
			
			
			
			$bmid = ''; 
			if ($qida->num_rows() > 0) {

				$rowa = $qida->row();
				$bmid = $rowa->bmid; 
				
				
			}else {
				
				echo $result;
				return false;		
								
			}
/*
			$this->db->select('literature_index');
			
			$qida = $this->db->get_where('literature',array('literature_id'=>$pid));
			
			$rowa = $query->row();

			$lit_ind = $rowa->literature_index;
		
	*/	
			$qida = $this->db->query("SELECT DISTINCT ac.acronym_full_name as afname,bs.brain_sites_index as bsind, bs.brain_sites_id as bsid FROM brain_sites bs JOIN brain_site_acronyms ac ON (bs.brain_sites_acronyms_id = ac.brain_site_acronyms_id )  WHERE brain_sites_index LIKE ? OR brain_sites_index LIKE ? AND brain_maps_id = ? LIMIT 7", array($qr.'%','%'.$qr.'%',$bmid));
		
			if ($qida->num_rows() > 0) {
		
				$result = "{ query:'" . $qr . "', suggestions:[";
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->bsind ." - ".$rowa->afname."',";
				}
		
				$result = substr($result, 0, strlen($result) - 1);
		
				$result .="],data:[";
		
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->bsid ."',";
				}
		
				$result = substr($result, 0, strlen($result) - 1);
					
					
				$result .="]}";
			}
		}
		
		echo $result;
		
		
		
	}
	
	
	
	function ajaxAtocomplitA(){
	
		$qr = $this->input->post('query');
	
		$pid = $this->session->userdata('pub_idA');
	
		$result = 'no thing';
	
		if ((!empty($qr)) && (!empty($pid)))  {
				
			$qida = $this->db->query("SELECT brain_maps_id as bmid FROM brain_maps WHERE literature_id = ?",array($pid));
				
				
				
			$bmid = '';
			if ($qida->num_rows() > 0) {
	
				$rowa = $qida->row();
				$bmid = $rowa->bmid;
	
	
			}else {
	
				echo $result;
				return false;
	
			}
			/*
				$this->db->select('literature_index');
				
			$qida = $this->db->get_where('literature',array('literature_id'=>$pid));
				
			$rowa = $query->row();
	
			$lit_ind = $rowa->literature_index;
	
			*/
			$qida = $this->db->query("SELECT DISTINCT ac.acronym_full_name as afname,bs.brain_sites_index as bsind, bs.brain_sites_id as bsid FROM brain_sites bs JOIN brain_site_acronyms ac ON (bs.brain_sites_acronyms_id = ac.brain_site_acronyms_id )  WHERE brain_sites_index LIKE ? OR brain_sites_index LIKE ? AND brain_maps_id = ? LIMIT 7", array($qr.'%','%'.$qr.'%',$bmid));
	
			if ($qida->num_rows() > 0) {
	
				$result = "{ query:'" . $qr . "', suggestions:[";
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->bsind ." - ".$rowa->afname."',";
				}
	
				$result = substr($result, 0, strlen($result) - 1);
	
				$result .="],data:[";
	
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->bsid ."',";
				}
	
				$result = substr($result, 0, strlen($result) - 1);
					
					
				$result .="]}";
			}
		}
	
		echo $result;
	
	
	
	}
	
	function ajaxAtocomplitB(){
	
		$qr = $this->input->post('query');
	
		$pid = $this->session->userdata('pub_idB');
	
		$result = 'no thing';
	
		if ((!empty($qr)) && (!empty($pid)))  {
				
			$qida = $this->db->query("SELECT brain_maps_id as bmid FROM brain_maps WHERE literature_id = ?",array($pid));
				
				
				
			$bmid = '';
			if ($qida->num_rows() > 0) {
	
				$rowa = $qida->row();
				$bmid = $rowa->bmid;
	
	
			}else {
	
				echo $result;
				return false;
	
			}
			/*
				$this->db->select('literature_index');
				
			$qida = $this->db->get_where('literature',array('literature_id'=>$pid));
				
			$rowa = $query->row();
	
			$lit_ind = $rowa->literature_index;
	
			*/
			$qida = $this->db->query("SELECT DISTINCT ac.acronym_full_name as afname,bs.brain_sites_index as bsind, bs.brain_sites_id as bsid FROM brain_sites bs JOIN brain_site_acronyms ac ON (bs.brain_sites_acronyms_id = ac.brain_site_acronyms_id )  WHERE brain_sites_index LIKE ? OR brain_sites_index LIKE ? AND brain_maps_id = ? LIMIT 7", array($qr.'%','%'.$qr.'%',$bmid));
	
			if ($qida->num_rows() > 0) {
	
				$result = "{ query:'" . $qr . "', suggestions:[";
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->bsind ." - ".$rowa->afname."',";
				}
	
				$result = substr($result, 0, strlen($result) - 1);
	
				$result .="],data:[";
	
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->bsid ."',";
				}
	
				$result = substr($result, 0, strlen($result) - 1);
					
					
				$result .="]}";
			}
		}
	
		echo $result;
	
	
	
	}
	
	
	function ajaxGetBrainSites(){
		
		$pid = $this->input->post('bm_id');
		$result = "empty input";
		
		if (!empty($pid)){
		
			
			$this->db->join('brain_site_acronyms','brain_sites.brain_sites_acronyms_id = brain_site_acronyms.brain_site_acronyms_id');
				
			$qida = $this->db->get_where('brain_sites',array('brain_maps_id' => $pid));
		
			$result = "no records";
		
			if ($qida->num_rows() > 0) {
		
		
				$this->data['lit_data'] = $qida;
				
				
				// collecting types and classes from db 
				
				$qida = $this->db->get('brain_sites_types');
				
				$this->data['type_options'] = array();
				
				foreach ($qida->result() as $rowa) {
						
					$this->data['type_options'][$rowa->brain_sites_type_id] = $rowa->brain_sites_type_name;
						
				}
				
				/*
				$qida = $this->db->get('brain_sites_classes');
				
				$this->data['classes_options'] = array();
				
				foreach ($qida->result() as $rowa) {
				
					$this->data['classes_options'][$rowa->brain_sites_classes_id] = $rowa->brain_sites_classes_name;
				
				}
				*/
				
		
		
				$result = $this->load->view('brainsites_ajax_get_view',$this->data,TRUE);
		
		
		
		
			}
		
		
		}
		
		
		echo $result;
		
		
		
		
		
		
		
	}
	
	
	
	function ajaxSetPublicationId(){
		
		$pid = $this->input->post('pubid');

		$this->session->set_userdata('pub_id',$pid);
		
	}
	
	
	function ajaxSetPublicationIdA(){
	
		$pid = $this->input->post('pubid');
	
		$this->session->set_userdata('pub_idA',$pid);
	
	}
	
	function ajaxSetPublicationIdB(){
	
		$pid = $this->input->post('pubid');
	
		$this->session->set_userdata('pub_idB',$pid);
	
	}
	
	function ajaxSetPublicationId2(){

		$pid2 = $this->input->post('pubid2');
	
		$this->session->set_userdata('pub_id2',$pid2);
	
	
	}
	
}

?>
