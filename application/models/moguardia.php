<?php
class Moguardia extends CI_Model {
	
	function __construct()
	{
		parent::__construct();
	}

	//¿Ha iniciado sesión?
	function session_exists()
	{
		$udata = $this->session->all_userdata();
		return isset($udata->user);
	}
	
	//Verificar que la sesión exista
	function isin()
	{
		$this->load->library('session');
		if(strlen($this->session->userdata('u')) == 0)
		{
			redirect(path());
			exit;
		}
	}

	//Info del usuario
	function session_info()
	{
		return $this->session->all_userdata();
	}
	
	//Módulos a los que tiene acceso
	function userModulos()
	{
		$this->load->library('session');
		$modulos = array();
		foreach($this->session->userdata('permisos') as $k => $v)
		{
			$modulos[] = $k;
		}
		
		return $modulos;
	}
	
	//Sacar del sistema si no existe sesión
	function permitido($t, $modulo = null)
	{
		$this->load->library('session');
		$permisos = $this->session->userdata('permisos');
		if(is_null($modulo)) $modulo = $this->router->class;
		if(in_array($modulo, $this->userModulos()))
		{
			return in_array($t, $permisos[$modulo]);
		}
		else
		{
			return false;
		}
	}
	
	//Series disponibles para este usuario
	function series()
	{
		$miUsuario = $this->session_info();
		$CI =& get_instance();
    $CI->load->model('mousuarios');
		$CI->mousuarios->init($miUsuario['u']);
		$miUsuarioInfo = $CI->mousuarios->infogral();
		return $miUsuarioInfo['serie'];
	}
}