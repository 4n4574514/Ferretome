<?php
class Acronyms extends Controller {

	private $data;

	function Acronyms()
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
		$this->load->view('acronyms_view',$this->data);


	}
	
	function show() {
	
	
	
		$this->data['action'] = 'show';
	
		$this->load->model('Acronyms_model','acm',TRUE);
	
		if (($qida = $this->acm->get_all(array(),'acronym_name','asc','999')) != FALSE){
				
				
			$this->data['block_data'] = $qida;
			$this->data['block_fields'] = $this->acm->get_fields();
				
				
				
		}
	
			
		$this->load->view('acronyms_view',$this->data);
	
	
	}
	
	
	function add(){
	
	
		$this->data['fields'] = $this->db->field_data('brain_site_acronyms');
	
		$this->data['action'] = 'add';
	
		//$this->data['extraHeader'] = '<script type="text/javascript" src="'.$this->config->system_url().'js/autocomplete.js"></script>';
		//$this->data['extraHeader'] .= '<script type="text/javascript" src="'.$this->config->system_url().'js/acronyms.js"></script>';
	
		// 		print_r($this->data);
			
		$this->load->view('acronyms_view',$this->data);
	
	
	}
	
	function insert() {
	
		$fields = $_POST;
	
	
		$authors = array();
	
		//$authors = $fields['authors_id'];
	
		//unset($fields['authors_id']);
	
	
// 		$fields['literature_mappingData'] = 0;
// 		$fields['literature_tracingData'] = 0;
	
		//print_r($fields);
			
	
		if ($this->db->insert('brain_site_acronyms',$fields) === FALSE){
	
			$this->data['add_message'] = "An error has occured";
			
			$this->add();
	
		}
		else {
			
			$lid = $this->db->insert_id();			
			$this->journal->newrecord($this->session->userdata('user_id'),5,$lid);
			
			$this->data['index_message'] = "Acronym was added";
			$this->index();
		}
	
	
	}
	
	
	
	
	function search(){
	
	
		//$this->data['fields'] = $this->db->field_data('literature');
	
		$this->data['action'] = 'search';
	
		$this->data['extraHeader'] = '<script type="text/javascript" src="js/autocomplete.js"></script>';
		$this->data['extraHeader'] .= '<script type="text/javascript" src="js/acronyms.js"></script>';
			
		$this->load->view('acronyms_view',$this->data);
	
	
	
	
	}
	
	function searchDo() {
	
		$id = $this->input->post('acrid');
	
		$result = 'nothing found: empty';
	
		if (!empty($id)){
	
			$qida = $this->db->get_where('brain_site_acronyms',array('brain_site_acronyms_id' => $id));
			
			$result = 'nothing found: no records';
				
			if ($qida->num_rows()>0){
	
	
				$this->data['acr_data'] = $qida->row();
	
				$result = $this->load->view('acronyms_search_view',$this->data);
	
	
			}
	
	
		}
	
		echo $result;
	
	
	}
	
	
	function edit() {
	
	
		$id = $this->input->get('id');
	
		$this->data['action'] = 'edit';
		
		$this->data['block_message'] = "Nothing was sended";
	
	
	
		if (!empty($id)){
	
	
			$qida = $this->db->get_where('brain_site_acronyms',array('brain_site_acronyms_id' => $id));
			
			$this->data['block_message'] = "Nothing was found";
				
			if ($qida->num_rows()>0){
	
				$this->data['acr_data'] = $qida->row();
				
				unset($this->data['block_message']);
	
				$this->data['fields'] = $this->db->field_data('brain_site_acronyms');
	
	
			}
		}
	
		$this->load->view('acronyms_view',$this->data);
			
	}
	
	function update() {
	
	
	
		$fields = $_POST;
	
		// 		print_r($fields);
	
	
		$id = $this->input->get('id');
	
	
		if (!empty($id)){
				
				
			$this->db->where('brain_site_acronyms_id',$id);
				
			if ($this->db->update('brain_site_acronyms',$fields) === FALSE){
	
				$this->search();
					
			} else {
					
	
				$this->journal->newrecord($this->session->userdata('user_id'),5,$id,2);
					
				$this->data['index_message'] = "Acronym was updated";
				$this->index();
			}
		}
	
	
	}
	
	
	
	
	
	
	
	
	function ajaxAtocomplit() {
		
		$qr = $this->input->post('query');
		
		$result = 'no thing';
		
		if (!empty($qr)) {
		
		
		
			$qida = $this->db->query("SELECT DISTINCT brain_site_acronyms_id as aid, acronym_name as aname, acronym_full_name as afname FROM brain_site_acronyms WHERE acronym_name LIKE ? LIMIT 10", array($qr . '%'));
// 			$qida = $this->db->query("SELECT DISTINCT brain_site_acronyms_id as aid, brain_site_acronyms_acronymName as aname, brain_site_acronyms_acronymFullName as afname FROM brain_site_acronyms WHERE brain_site_acronyms_acronymName LIKE ? OR brain_site_acronyms_acronymFullName LIKE ? LIMIT 7", array($qr . '%',$qr . '%'));
		
			if ($qida->num_rows() > 0) {
		
				$result = "{ query:'" . $qr . "', suggestions:[";
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->aname ." - ". substr($rowa->afname,0, 60). "',";
				}
		
				$result = substr($result, 0, strlen($result) - 1);
		
				$result .="],data:[";
		
				foreach ($qida->result() as $rowa) {
					$result .= "'". $rowa->aid ."',";
				}
		
				$result = substr($result, 0, strlen($result) - 1);
					
					
				$result .="]}";
			}
		}
		
		echo $result;
		
		
	}
	
	


}
?>