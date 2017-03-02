<?php
//Administra las sesiones de Lola
class Mousuarios extends CI_Model
{
	var $id = 0;
	function __construct()
	{
		parent::__construct();
	}
	
	function init($id)
	{
		$this->id = $id;
	}
	
	function infogral()
	{
		$this->load->database();
		$q = $this->db->get_where('usuarios', array( 'user' => $this->id ));
		if($q->num_rows() > 0)
		{
			$info = $q->row_array();
			foreach($this->db->get_where('usuarios_series', array( 'user' => $this->id ))->result() as $r)
			{
				$info['serie'][] = $r->serie;
			}
			return $info;
		}
		else
		{
			return 0;
		}
	}
}