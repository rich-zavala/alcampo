<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Clientes extends CI_Controller {

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
			if(strlen($orden) == 0) $orden = 'nombre';
			if(strlen($direccion) == 0) $direccion = 'ASC';
			if(strlen($mostrar) == 0) $mostrar = 20;
			
			//Valores para el modelo
			$w = array();
			if(strlen($filtro) > 0)
			{
				$w[] = "(id = '{$filtro}' OR nombre LIKE '%{$filtro}%' OR tel LIKE '%{$filtro}%' OR email LIKE '%{$filtro}%')";
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
		
		$h['titulo'] = "Clientes";
		$d['titulo'] = $h['titulo'];
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('clientes_list.html', $d);
		$this->load->view('lay/footer.html');
	}
	
	public function formulario($id = null, $evento = null)
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$id = ($id != 'sin_cliente') ? $id : null;
		$d['e'] = 0;
		if($this->moguardia->permitido('registrar'))
		{
			//Inicialización
			$d['edicion'] = false;
			$d['controlador'] = $this->router->class;
			$d['titulo'] = "Registro de  nuevo cliente";
			
			//Valores por default
			$d['info'] = array(
				'id' => $id,
				'nombre' => null,
				'email' => null,
				'tel' => null,
				'direccion' => null
			);
			$d['categorias'] = array();
			
			//Catálogo de categorias
			$this->load->model('moproductos');
			$d['catalogo'] = $this->moproductos->por_linea();
			
			if(!is_null($id))
			{
				$this->load->model('mocliente');
				$this->mocliente->init($id);
				$d['edicion'] = true;
				$d['titulo'] = "Edición de cliente [<b>{$id}</b>]";
				$d['info'] = $this->mocliente->infogral();
				$d['categorias'] = $this->mocliente->productos();
				$d['imagenes'] = $this->mocliente->imagenes();
				// p($d); exit;
			}
		}
		else
		{
			$d['e']++;
			$d['msg'] = 'No cuenta con los permisos suficientes para el acceso a este módulo.';
		}
		
		//Mensajes de eventos
		switch($evento)
		{
			case 'actualizado': $d['evento'] = "Los cambios en el cliente <b>{$id}</b> se han registrado exitosamente."; break;
			case 'registrado': $d['evento'] = "El cliente ha sido registrado exitosamente con <b>ID {$id}</b>. Ya puede asignarle ventas."; break;
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
		
		// p($d);
		
		$h['titulo'] = strip_tags($d['titulo']);
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('clientes_formulario.html', $d);
		$this->load->view('lay/footer.html');
	}

	public function registro()
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$msg = array();
		if($this->moguardia->permitido('registrar'))
		{
			$id = (int)trim($this->input->post('id', true));
			$nombre = trim($this->input->post('nombre', true));
			$email = trim($this->input->post('email', true));
			$tel = trim($this->input->post('tel'));
			$direccion = trim($this->input->post('direccion'));
			$categoria = (isset($_POST['categoria']) > 0) ? $this->input->post('categoria', true) : array();
			$tope = (isset($_POST['tope']) > 0) ? $this->input->post('tope', true) : array();
			$tipo = (isset($_POST['tipo']) > 0) ? $this->input->post('tipo', true) : array();
			$registro = (isset($_POST['registro']) > 0) ? $this->input->post('registro', true) : array();
			
			// p($_POST); exit;
			
			//Validar campos
			if(strlen($nombre) < 6) $msg[] = 'La información en el campo "Nombre" debe contener un mínimo de 6 caracteres.';
			if(count($categoria) == 0 or array_sum($tope) == 0) $msg[] = 'No se seleccionaron productos de venta a este cliente.';
			
			//Arreglo de inserción
			$i = array(
				'id' => $id,
				'nombre' => $nombre,
				'email' => $email,
				'tel' => $tel,
				'direccion' => $direccion,
				'status' => 0
			);
			
			if(count($msg) == 0)
			{
				if($id > 0) //Si no tiene password, debe ser un cliente previamente registrado. ¡EDICIÓN!
				{
					if($this->db->get_where('clientes', array('id' => $id))->num_rows() > 0)
					{
						$this->db->where('id', $id);
						$this->db->update('clientes', $i);
						$t = 'actualizado';
					}
					else
					{
						echo "El cliente editado no existe. [Error de sistema]"; exit;
					}
				}
				else //Inserción nueva
				{
					if($this->db->get_where('clientes', array('nombre' => $nombre))->num_rows() == 0)
					{
						$this->db->insert('clientes', $i);
						$id = $this->db->insert_id();
						$t = 'registrado';
					}
					else
					{
						$msg[] = "El nombre del cliente ya está registrado.";
					}
				}
				
				//Ingresar productos
				$this->db->where('cliente', $id);
				$this->db->delete('clientes_productos');
				foreach($categoria as $k => $c)
				{
					$s = "SELECT IFNULL((SELECT SUM(id) FROM clientes_productos WHERE cliente = {$id} AND registro = '{$registro[$k]}'), 0) c";
					if($this->db->query($s)->row()->c == 0)
					{
						$i = array(
							'cliente' => $id,
							'categoria' => $c,
							'tope' => $tope[$k],
							'tipo' => $tipo[$k],
							'registro' => $registro[$k]
						);
						if($tope[$k] > 0) $this->db->insert('clientes_productos', $i);
						echo $this->db->last_query()."\n";
					}
				}
			}
			else
			{
				$t = 'error';
				$this->session->set_flashdata('info', $i);
				$this->session->set_flashdata('productos', $categoria);
				$this->session->set_flashdata('errores', $msg);
			}
			
			$url = path().$this->router->class.'/formulario/';
			if($id > 0)
			{
				$url .= $id.'/'.$t;
			}
			else
			{
				$this->session->set_flashdata('info', $i);
				$this->session->set_flashdata('errores', $msg);
				$url .= 'sin_cliente/error';	
			}
			// exit;
			redirect($url.suffix());
		}
		else{ show_404(); }
	}
	
	public function imagenes()
	{
		error_reporting(-1);
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$msg = array();
		if($this->moguardia->permitido('registrar'))
		{
			$id = (int)trim($this->input->post('id', true));
			if(count($_FILES['imagenes']) > 0)
			{
				$f = $_FILES['imagenes'];
				$this->load->helper('file');
				$this->load->library('image_lib');
				
				foreach($f['tmp_name'] as $ki => $img)
				{
					if($f['type'][$ki] == 'image/jpeg')
					{
						$path = 'r_/expedientes/' . $id;
						@mkdir($path);
						$nombre = rand() * rand();
						$p_nombre = $path . '/' . $nombre;
						$i_nombre = $p_nombre . '.jpg';
						move_uploaded_file($img, $i_nombre);
						
						//Generar thumb
						$config = array(
							'image_library' => 'gd2',
							'source_image'	=> $i_nombre,
							'maintain_ratio' => TRUE
						);
					
						//Generar Thumb
						$tmp_path = $path . '/' . $nombre . '_thumb.jpg';
						$config['new_image'] = $tmp_path;
						$config['width'] = 200;
						$config['height'] = 200;
						$this->image_lib->initialize($config); 
						$this->image_lib->resize();
						
						
						// exit;
						/*
						//Subir imagen temporal
						$original = 'r_/tmp/or_'.rand().'.jpg';
						move_uploaded_file($img, $original);
					
						//Verificar tamaño del original
						$intento = 0;
						$imgData = get_file_info($original);
						
						$imgSize = $imgData['size'];
						$quality = 90;
						if($imgSize > 921600)
						{
							while($imgSize > 921600)
							{
								$vals = @getimagesize($original);
								$new_original = 'r_/tmp/or_new_'.rand().'.jpg';
								$configo = array(
									'image_library' => 'gd2',
									'source_image'	=> $original,
									'maintain_ratio' => TRUE,
									'height' => $vals[1] - 1,
									'width' => $vals[0] - 1,
									'new_image' => $new_original,
									'quality' => $quality
								);
								$this->image_lib->initialize($configo); 
								$this->image_lib->resize();
								
								//Reemplazar archivos
								@unlink($original);
								rename($new_original, $original);
								
								$quality -= 20;
								$imgData = get_file_info($original);
								$imgSize = $imgData['size'];
							}
						}
						
						//Configuración de transformador de imagen
						$config = array(
							'image_library' => 'gd2',
							'source_image'	=> $original,
							'maintain_ratio' => TRUE
						);
						
						//Ingresar imagen original
						$original64 = file_get_contents($original);
					
						//Generar Thumb
						$tmp_path = 'r_/tmp/th_'.rand().'.jpg';
						$config['new_image'] = $tmp_path;
						$config['width'] = 200;
						$config['height'] = 200;
						$this->image_lib->initialize($config); 
						$this->image_lib->resize();
						$thumb = file_get_contents($tmp_path);
						@unlink($original);
						@unlink($tmp_path);

						$i = array(
							'cliente' => $id,
							'imagen' => $original64
						);
						$this->db->insert('clientes_expedientes', $i);

						$this->db->where('id', $this->db->insert_id());
						$i = array( 'micro' => $thumb );
						$this->db->update('clientes_expedientes', $i);
						*/
					}
					else
					{
						$msg[0] = "Hubieron imágenes con formato incorrecto.";
					}
				}
				// exit;
			}
			else
			{
				$msg[] = "No se seleccionaron imágenes.";
			}
			if(count($msg) > 0) $this->session->set_flashdata('errores', $msg);
			$url = path().$this->router->class.'/formulario/'.$id;
			redirect($url.suffix().'#expediente');
		}
		else{ show_404(); }
	}

	public function restore()
	{
		set_time_limit(0);
		$this->load->helper('file');
		foreach($this->db->get('clientes_expedientes')->result() as $c)
		{
			$path = 'r_/expedientes/' . $c->cliente;
			@mkdir($path);
			
			$img = $path . '/' . $c->id . '.jpg';
			if(!file_exists($img))
			{
				write_file($img, $c->imagen);
				
				$micro = $path . '/' . $c->id . '_thumb.jpg';
				write_file($micro, $c->micro);
			}
			// exit;
		}
	}
}