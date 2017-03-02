<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Acceso extends CI_Controller {

	public function index($error = '')
	{
		$this->load->helper('url');
		if(substr_count(current_url(), 'www') > 0)
		{
			redirect(base_url());
			exit;
		}
	
		$this->session->unset_userdata(array('u' => null, 'nombre' => null, 'email' => null, 'permisos' => null)); //Reseteo de sesión
		
		//Verificar intento de logueo
		$d['error'] = 0;
		$this->load->library('form_validation');
		$this->form_validation->set_rules('user', 'Usuario', 'required');
		$this->form_validation->set_rules('pass', 'Contraseña', 'required');
		if($this->form_validation->run())
		{
			$user = trim($this->input->post('user', TRUE));
			$pass = trim($this->input->post('pass', TRUE));
			
			if(strlen($user) > 4 && strlen($pass) > 4)
			{
				$this->load->database();
				$this->db->where(array( 'user' => $user, 'pass' => md5($pass), 'status' => 0 ));
				$q = $this->db->get('usuarios');
				if($q->num_rows() > 0) //El usuario existe y está activo
				{
					$this->db->where('user', $user);
					$qp = $this->db->get('usuarios_permisos');
					if($qp->num_rows() > 0) //Logueo exitoso
					{
						$u = array();
						$r = $q->row();
						$u = array(
							'u' => $r->user,
							'nombre' => $r->nombre,
							'email' => $r->email,
							'permisos' => array()
						);
						foreach($qp->result() as $p) $u['permisos'][$p->modulo][] = $p->permiso;
						$this->load->library('session');
						$this->session->set_userdata($u);
						
						//Redirigir
						if(isset($u['permisos']['ventas']))
						{
							redirect(path() . 'ventas' . suffix());
						}
						else
						{
							redirect(path() . key($u['permisos']) . suffix());
						}
					}
					else //El usuario no tiene permisos
					{
						$d['error']++;
						$d['msg'] = 'El usuario no tiene permisos en el sistema.';
					}
				}
				else //No existe o está inactivo
				{
					$d['error']++;
					$d['msg'] = 'El nombre de usuario o contraseña no son incorrectos.';
				}
			}
			else //Entró mal el formulario
			{
				$d['error']++;
				$d['msg'] = 'El nombre de usuario o contraseña no son válidos.';
			}
		}
		
		$h['titulo'] = "Acceso a sistema";
		$this->load->view('lay/includes.html', $h);
		$this->load->view('login.html', $d);
		$this->load->view('lay/footer.html');
	}
}