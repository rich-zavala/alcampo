<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Herramientas extends CI_Controller {
		
	public function index($error = '')
	{
		show_404();
	}
	
	//Cambios de status
	public function cambios()
	{
		if($this->input->is_ajax_request())
		{
			$controlador = trim($this->input->post('controlador'));
			$this->load->model('moguardia');
			$this->moguardia->isin();
			$resultado = array('error' => 0);
			$valor = trim($this->input->post('value'));
			$permiso = ($valor != '-1') ? 'cancelar' : 'eliminar';
			if($this->moguardia->permitido($permiso, $controlador))
			{
				$table = trim($this->input->post('table'));
				$key = trim($this->input->post('key'));
				$id = trim($this->input->post('id'));

				$this->db->where(array( $key => $id ));
				$this->db->update($table, array('status' => $valor));
			}
			else
			{
				$resultado['error']++;
			}
			echo json_encode($resultado);
		}
		else
		{show_404('chpass.noajax');}
	}
	
	//Información de un cliente
	public function cliente_data($cliente, $fecha = '')
	{
		if($this->input->is_ajax_request())
		{
			if($fecha == '') $fecha = date('Y-m-d');
			$resultado = array('error' => 0);
			$this->load->model('mocliente');
			$this->mocliente->init($cliente, $fecha);
			$data = $this->mocliente->infogral();
			if(!is_int($data))
			{
				$resultado['cliente'] = $data;
			}
			else
			{
				$resultado['error']++;
			}
		
			echo json_encode($resultado);
		}
		else
		{show_404('chpass.noajax');}
	}
	
	//Facturas de cliente
	public function cliente_facturas($cliente)
	{
		if($this->input->is_ajax_request())
		{
			$resultado = array('error' => 0);
			$q = $this->db->get_where("ventas_cliente", array('cliente' => $cliente));
			if($q->num_rows() > 0)
			{
				$resultado['data'] = $q->result();
			}
			else
			{
				$resultado['error']++;
			}
		
			echo json_encode($resultado);
		}
		else
		{show_404('chpass.noajax');}
	}
	
	//Buscador de clientes
	public function cliente_busqueda()
	{
		if($this->input->is_ajax_request())
		{
			$cliente = trim($this->input->post('cliente'));
			$resultado = array('error' => 0, 'clientes' => array());
			$this->load->database();
			$s = "SELECT * FROM clientes WHERE status = 0 AND ( id LIKE '%{$cliente}%' OR nombre LIKE '%{$cliente}%')";
			$q = $this->db->query($s);
			if($q->num_rows() > 0)
			{
				$resultado['clientes'][] = $q->result_array();
			}
			else
			{
				$resultado['error']++;
			}
			
			echo json_encode($resultado);
		}
		else
		{show_404('chpass.noajax');}
	}

	//Eliminar imagen
	public function imagen_eliminar()
	{
		if($this->input->is_ajax_request())
		{
			$this->load->model('moguardia');
			$this->moguardia->isin();
			@unlink(trim($this->input->post('imagen')));
			@unlink(trim($this->input->post('thumb')));
			echo 1;
		}
		else
		{show_404('chpass.noajax');}
	}
	
	//Mostrar gran imagen
	public function imagen_show($id)
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$this->db->where('id', $id);
		$i = $this->db->get('clientes_expedientes')->result();
		header("Content-type: image/jpeg");
		echo $i[0]->imagen;
		die();
		exit;
	}
	
	//Copiar folio A > B
	public function copiar()
	{
		if($this->input->is_ajax_request())
		{
			$this->load->model('moguardia');
			$this->moguardia->isin();
			$resultado = array('error' => 0);
			if($this->moguardia->permitido('copiar', 'ventas'))
			{
				//Datos de la venta original
				$id = (int)trim($this->input->post('id'));
				$this->load->model('mocomun');
				$serie_a = $this->mocomun->venta_data($id);
				$serie_b = $this->mocomun->venta_existe('B', $serie_a->n);
				if($serie_b == 0) $serie_b = $this->mocomun->venta_copia($id);
				$resultado['id'] = $serie_b;
			}
			else
			{
				$resultado['error']++;
			}
			echo json_encode($resultado);
		}
		else
		{show_404('chpass.noajax');}
	}
}