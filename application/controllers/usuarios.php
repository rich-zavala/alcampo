<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Usuarios extends CI_Controller {

	//Listado
	public function index($pagina = 1)
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$d['e'] = 0;
		if($this->moguardia->permitido('consultar'))
		{
			//Inicialización
			$d['pagina'] = $pagina;
			$d['controlador'] = $this->router->class;
			$d['permisos'] = array(
				'registrar' => $this->moguardia->permitido('registrar'),
				'cancelar' => $this->moguardia->permitido('cancelar'),
				'eliminar' => $this->moguardia->permitido('eliminar')
			);
			
			//Filtros
			$filtro = trim($this->input->post('filtro', true));
			$status = trim($this->input->post('status', true));
			$orden = trim($this->input->post('orden', true));
			$direccion = trim($this->input->post('direccion', true));
			$mostrar = trim($this->input->post('mostrar', true));
			
			//Valores por default
			if(strlen($status) == 0) $status = -1;
			if(strlen($orden) == 0) $orden = 'user';
			if(strlen($direccion) == 0) $direccion = 'ASC';
			if(strlen($mostrar) == 0) $mostrar = 20;
			
			//Valores para el modelo
			$w = array();
			if(strlen($filtro) > 0)
			{
				$w[] = "(user LIKE '%{$filtro}%' OR nombre LIKE '%{$filtro}%' OR email LIKE '%{$filtro}%')";
			}
			
			//Límites y orden
			if(strlen($status) > 0 and $status > -1) $w[] = "status = {$status}";
			$limit = array( $mostrar, ($pagina - 1) * $mostrar );
			$orden = array( $orden, $direccion );
			
			//Ejecutar listado
			$this->load->model('mocomun');
			$registros = $this->mocomun->listado($d['controlador'], $w, $orden, $limit);
			$d['registros'] = $registros['resultados'];
			
			//Paginación
			$d['pagination'] = $this->mocomun->pagination($d['controlador'], $mostrar, $registros['total']);
			
			//Reasignar valores para su devolución
			$d['f'] = array(
				'filtro' => $filtro,
				'status' => $status,
				'orden' => $orden,
				'direccion' => $direccion,
				'mostrar' => $mostrar
			);
		}
		else
		{
			$d['e']++;
			$d['msg'] = 'No cuenta con los permisos suficientes para el acceso a este módulo.';
		}
		
		$h['titulo'] = "Usuarios";
		$d['titulo'] = $h['titulo'];
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('usuarios_list.html', $d);
		$this->load->view('lay/footer.html');
	}
	
	public function formulario($uid = null, $evento = null)
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$uid = ($uid != 'sin_usuario') ? urldecode($uid) : null;
		$d['e'] = 0;
		if($this->moguardia->permitido('registrar'))
		{
			//Inicialización
			$d['controlador'] = $this->router->class;
			$d['titulo'] = "Registro de  nuevo usuario";
			
			//Lista de permisos
			$d['modulos'] = array();
			foreach($this->db->get('modulos')->result() as $r)
			{
				if($r->accion == 'consultar')
				{
					$d['modulos'][$r->modulo]['titulo'] = $r->titulo;
				}
				else
				{
					$d['modulos'][$r->modulo]['permisos'][$r->accion] = $r->titulo;
				}
			}
			
			//Valores por default
			$d['info'] = array(
				'user' => null,
				'pass' => null,
				'nombre' => null,
				'email' => null,
				'series' => array()
			);
			$d['p'] = array();
			if(!is_null($uid))
			{
				$d['edicion'] = true;
				$d['titulo'] = "Edición de usuario [<b>{$uid}</b>]";
				$d['info'] = $this->db->get_where('usuarios', array('user' => $uid))->row_array();
				foreach($this->db->get_where('usuarios_permisos', array('user' => $uid))->result() as $r) $d['p'][$r->modulo][] = $r->permiso;
				$d['info']['series'] = array();
				foreach($this->db->get_where('usuarios_series', array('user' => $uid))->result() as $r) $d['info']['series'][] = $r->serie;
			}
			
			//Series disponibles
			$d['series'] = array();
			foreach($this->db->get('series')->result() as $r) $d['series'][] = $r->serie;
		}
		else
		{
			$d['e']++;
			$d['msg'] = 'No cuenta con los permisos suficientes para el acceso a este módulo.';
		}
		
		//Mensajes de eventos
		switch($evento)
		{
			case 'actualizado': $d['evento'] = 'Los cambios en el usuario se han registrado exitosamente.'; break;
			case 'registrado': $d['evento'] = 'El usuario ha sido registrado exitosamente. Ya puede iniciar sesión.'; break;
			case 'error':
				$d['error'] = true;
				$flash = $this->session->flashdata('errores');
				$d['info'] = $this->session->flashdata('info');
				$d['p'] = $this->session->flashdata('permisos');
				if(is_array($flash))
				{
					$d['evento'] = "<ul><b>Han ocurrido los siguientes errores de llenado de formulario:</b>";
					foreach($flash as $e)
					{
						$d['evento'] .= "<li>{$e}</li>";
					}
					$d['evento'] .= "</ul>";
				}
				else
				{
					$d['evento'] = "Ha ocurrido un error no identificado.";
				}
			break;	
		}
		
		$h['titulo'] = strip_tags($d['titulo']);
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('usuarios_formulario.html', $d);
		$this->load->view('lay/footer.html');
	}

	public function registro()
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$msg = array();
		if($this->moguardia->permitido('registrar'))
		{
			$user = trim($this->input->post('user', true));
			$pass = trim($this->input->post('pass'));
			$pass2 = trim($this->input->post('pass2'));
			$nombre = trim($this->input->post('nombre', true));
			$email = trim($this->input->post('email', true));
			$permisos = (isset($_POST['permisos']) > 0) ? $this->input->post('permisos', true) : array();
			$series = (isset($_POST['series']) > 0) ? $this->input->post('series', true) : array();
			
			//Validar campos
			if(strlen($user) < 6) $msg[] = 'La información en el campo "Usuario" debe contener un mínimo de 6 caracteres.';
			if(strlen($nombre) < 6) $msg[] = 'La información en el campo "Nombre" debe contener un mínimo de 6 caracteres.';
			if(count($permisos) == 0) $msg[] = 'No seleccionó ningún permiso para este usuario.';
			if(count($series) == 0) $msg[] = 'No seleccionó ninguna serie de venta.';
			
			//Arreglo de inserción
			$i = array(
				'user' => $user,
				'nombre' => $nombre,
				'email' => $email,
				'status' => 0,
				'permisos' => $permisos,
				'series' => $series
			);
			
			if(count($msg) == 0)
			{
				if(strlen($pass) == 0) //Si no tiene password, debe ser un usuario previamente registrado. ¡EDICIÓN!
				{
					if($this->db->get_where('usuarios', array('user' => $user))->num_rows() > 0)
					{
						unset($i['permisos'], $i['series']);
						$this->db->where('user', $user);
						$this->db->update('usuarios', $i);
						$t = 'actualizado';
					}
					else
					{
						echo "El usuario editado no existe. [Error de sistema]"; exit;
					}
				}
				else //Inserción nueva
				{
					if($this->db->get_where('usuarios', array('user' => $user))->num_rows() == 0)
					{
						if($pass == $pass2)
						{
							unset($i['permisos'], $i['series']);
							$i['pass'] = md5($pass);
							$this->db->insert('usuarios', $i);
							$t = 'registrado';
						}
						else
						{
							$msg[] = 'Las contraseña ingresada no fue comprobada.';
						}
					}
					else
					{
						$msg[] = "El nombre de usuario \"{$user}\" ya está registrado.";
					}
				}
				
				//Ingresar permisos
				$this->db->where('user', $user);
				$this->db->delete('usuarios_permisos');
				foreach($permisos as $modulo => $permiso)
				{
					foreach($permiso as $p)
					{
						$this->db->insert('usuarios_permisos', array( 'user' => $user, 'modulo' => $modulo, 'permiso' => $p ));
					}
				}
				
				//Ingresar series
				$this->db->where('user', $user);
				$this->db->delete('usuarios_series');
				foreach($series as $s) $this->db->insert('usuarios_series', array( 'user' => $user, 'serie' => $s ));
			}
			else
			{
				$t = 'error';
				$this->session->set_flashdata('info', $i);
				$this->session->set_flashdata('permisos', $permisos);
				$this->session->set_flashdata('series', $series);
				$this->session->set_flashdata('errores', $msg);
			}
			
			$url = path().$this->router->class.'/formulario/';
			if(!is_array($this->session->flashdata('errores')))
			{
				$url .= $user.'/'.$t;
			}
			else
			{
				$url .= 'sin_usuario/error';	
			}
			// echo $url;
			redirect($url.suffix());
		}
	}
}