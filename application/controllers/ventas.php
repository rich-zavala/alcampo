<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ventas extends CI_Controller {

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
				'copiar' => $this->moguardia->permitido('copiar'),
				'cambiar' => $this->moguardia->permitido('cambiar'),
				'eliminar' => $this->moguardia->permitido('eliminar')
			);
			
			//Info del usuario (Obtener serie)
			$d['series'] = $this->moguardia->series();
			
			//Filtros
			$filtro = trim($this->input->post('filtro', true));
			$status = trim($this->input->post('status', true));
			$orden = trim($this->input->post('orden', true));
			$direccion = trim($this->input->post('direccion', true));
			$mostrar = trim($this->input->post('mostrar', true));
			$fecha_venta1 = trim($this->input->post('fecha_venta1', true));
			$fecha_venta2 = trim($this->input->post('fecha_venta2', true));
			$serie = trim($this->input->post('serie', true));
			
			//Valores por default
			if(strlen($status) == 0) $status = -1;
			if(strlen($orden) == 0) $orden = "serie_folio";
			if(strlen($direccion) == 0) $direccion = 'DESC';
			if(strlen($serie) == 0) $serie = '';
			if(strlen($mostrar) == 0) $mostrar = 20;
			
			//Valores para el modelo
			$w = array();
			if(strlen($filtro) > 0) $w[] = "(CONCAT(serie, '-', folio) = '{$filtro}' OR serie = '{$filtro}' OR folio = '{$filtro}' OR cliente_id = '{$filtro}' OR cliente LIKE '%{$filtro}%')";
			if(strlen($serie) > 0) $w[] = "serie = '{$serie}'";
			
			//Filtros de fecha
			$fw = array();
			if(strlen($fecha_venta1) > 0 and strlen($fecha_venta2) > 0){ $fw[] = "fecha_venta BETWEEN '{$fecha_venta1}' AND '{$fecha_venta2}'"; } else { $fecha_venta1 = ''; $fecha_venta2 = ''; }
			if(count($fw) > 0) $w[] = implode($fw, ' AND ');
			
			//Límites y orden
			if(strlen($status) > 0 and $status > -1) $w[] = "status = {$status}";
			$limit = array( $mostrar, ($pagina - 1) * $mostrar );
			$orden = array( $orden, $direccion );
			
			//Ejecutar listado
			$this->load->model('mocomun');
			$registros = $this->mocomun->listado('ventas_list', $w, $orden, $limit);
			$d['registros'] = $registros['resultados'];
			
			//Paginación
			$d['pagination'] = $this->mocomun->pagination($d['controlador'], $mostrar, $registros['total']);
			
			//Reasignar valores para su devolución
			$d['f'] = array(
				'filtro' => $filtro,
				'status' => $status,
				'orden' => $orden,
				'direccion' => $direccion,
				'fecha_venta1' => $fecha_venta1,
				'fecha_venta2' => $fecha_venta2,
				'serie' => $serie,
				'mostrar' => $mostrar
			);
		}
		else
		{
			$d['e']++;
			$d['msg'] = 'No cuenta con los permisos suficientes para el acceso a este módulo.';
		}
		
		$h['titulo'] = "Ventas";
		$d['titulo'] = $h['titulo'];
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('ventas_list.html', $d);
		$this->load->view('lay/footer.html');
	}
	
	public function formulario()
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$d['e'] = 0;
		if($this->moguardia->permitido('registrar'))
		{
			//Inicialización
			$d['controlador'] = $this->router->class;
			
			//Info del usuario (Obtener serie)
			$d['series'] = $this->moguardia->series();

			/*Obtener líneas de productos*/
			$this->load->model('moproductos');
			$d['lineas'] = $this->moproductos->lineas();
			$cats = array();
			foreach($d['lineas'] as $linea) $cats['l' . md5($linea)] = $this->moproductos->linea_categorias($linea);
			$d['categorias'] = json_encode($cats);
			
			$d['productos'] = '';
			$s = "SELECT p.id, p.categoria, p.descripcion, precio, total, CONCAT('l',MD5(linea)) linea, 0 vendidos
						FROM productos AS p INNER JOIN categorias AS c ON p.categoria = c.id";
			$q = $this->db->query($s);
			if($q->num_rows()) $d['productos'] = json_encode($q->result_array());
		}
		else
		{
			$d['e']++;
			$d['msg'] = 'No cuenta con los permisos suficientes para el acceso a este módulo.';
		}
		
		//Lista de clientes
		$d['clientes_nombres'] = array();
		$d['clientes_indices'] = array();
		foreach($this->db->where('status', 0)->get('clientes')->result() as $c)
		{
			$d['clientes_nombres'][] = $c->nombre;
			$d['clientes_indices'][(int)$c->id] = $c->nombre;
		}
		
		//Clientes con índice
		
		$d['titulo'] = "Registro de venta";
		$h['titulo'] = $d['titulo'];
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('ventas_formulario.html', $d);
		$this->load->view('lay/footer.html');
	}

	public function registro()
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		if($this->moguardia->permitido('registrar'))
		{
			$serie = 'A';
			$cliente = (int)trim($this->input->post('cliente', true));
			$fecha = trim($this->input->post('fecha', true));
			$cantidad = $this->input->post('cantidad', true);
			$producto = $this->input->post('producto', true);
			$total = $this->input->post('total', true);
			$registro = $this->input->post('registro', true);
			
			//Validaciones obligatorias
			$e = array();
			if($cliente <= 0) $e[] = "No se ha identificado al cliente.";
			if($fecha == '') $e[] = "La fecha de la venta no fue definida.";
			$productos_count = 0;
			$pi = array();
			if(isset($producto))
			{
				$this->load->model('moproductos');
				foreach($producto as $k => $p)
				{
					if(trim($p) != '' and (int)$cantidad[$k])
					{
						$pData = $this->moproductos->producto_info($p);
						$precio = $total[$k] / $cantidad[$k];
						$_i1 = $precio / (1 + ($pData->iva / 100));
						$iva = $precio - $_i1;
						
						//Subtotal
						$subtotal = $cantidad[$k] * ($precio - $iva);
						
						//IVA
						$iva_moneda = $cantidad[$k] * $iva;
						
						//Total
						$_total = $total[$k];
						
						$pi[] = array(
							'producto' => $p,
							'registro' => $registro[$k],
							'cantidad' => $cantidad[$k],
							'iva' => 16,
							'iva_moneda' => $iva_moneda,
							'precio' => $subtotal / $cantidad[$k],
							'subtotal' => $subtotal,
							'total' => $_total
						);
						$productos_count++;
					}
				}
			}
			
			if($productos_count == 0) $e[] = "No se definió ningún artículo";
			
			if(count($e) == 0) //No hay errores. Registrar.
			{
				//Obtener el folio de esta serie
				$this->db->insert('serie_' . $serie, array( 'serie' => $serie ));
				$folio = $this->db->insert_id();
				$i = array(
					'serie' => $serie,
					'folio' => $folio,
					'cliente' => $cliente,
					'user' => $this->session->userdata('u'),
					'fecha_venta' => $fecha,
					'fecha_registro' => date('Y-m-d H:i:s')
				);
				$this->db->insert('ventas', $i);
				$id = $this->db->insert_id();
				foreach($pi as $k => $v) $pi[$k]['venta'] = $id;
				$this->db->insert_batch('ventas_productos', $pi);
				
				//Reconstruir importe
				$this->db->simple_query("UPDATE ventas v SET importe = (SELECT SUM(total) FROM ventas_productos WHERE venta = v.id) WHERE id = {$id}");
				
				//Crear su respectivo folio B :D
				$this->load->model('mocomun');
				$this->mocomun->venta_copia($id);
				
				redirect(path().$this->router->class.'/detalle/'.$id.suffix());
				
			}
			else //Errores :(
			{
				echo "Se encontraron los siguientes conflictos que no permitieron registrar esta venta:<ul>";
				foreach($e as $r) echo "<li>{$r}</li>";
				echo "</ul>";
				exit;
			}
		}
	}

	public function detalle($id)
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$d['e'] = 0;
		if($this->moguardia->permitido('consultar') and (int)$id > 0)
		{
			$d['permisos'] = array(
				'cambiar' => $this->moguardia->permitido('cambiar')
			);
			
			$d['controlador'] = $this->router->class;
		
			//Datos de la venta
			$this->load->model('mocomun');
			$d['venta'] = $this->mocomun->venta_data($id);
			
			//¿Es serie A con equivalente B?
			$d['folioB'] = 0;
			if($d['venta']->serie == 'A')
			{
				$q = $this->db->get_where('ventas', array( 'serie' => 'B', 'folio' => $d['venta']->n ));
				if($q->num_rows() > 0) $d['folioB'] = $q->row()->id;
			}
			
			//¿Es serie B con equivalente A?
			$d['folioA'] = 0;
			if($d['venta']->serie == 'B')
			{
				$q = $this->db->get_where('ventas', array( 'serie' => 'A', 'folio' => $d['venta']->n ));
				if($q->num_rows() > 0) $d['folioA'] = $q->row()->id;
			}
			
			//Datos de productos
			$d['productos'] = $this->db->get_where('ventas_productos', array('venta' => $id))->result();
			
			//Datos del cliente
			$this->load->model('mocliente');
			$this->mocliente->init($d['venta']->cliente);
			$d['cliente'] = $this->mocliente->infogral();
		}
		else
		{
			$d['e']++;
			$d['msg'] = 'No cuenta con los permisos suficientes para el acceso a este módulo.';
		}
		
		//Datos de productos para PDF
		$d['productos'] = $this->mocomun->venta_productos($id);
		
		//Generar PDF
		$d['doc'] = "r_/ventas/{$d['venta']->folio}.pdf";
		@unlink($d['doc']);
		$html = $this->load->view('ventas_nota.html', $d, true);
		$this->load->helper(array('dompdf', 'file')); 
		pdf_create(utf8_decode($html), $d['doc']);
		
		$d['titulo'] = "Detalles de venta [<b>{$d['venta']->folio}</b>]";
		$h['titulo'] = strip_tags($d['titulo']);
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('ventas_detalle.html', $d);
		$this->load->view('lay/footer.html');
	}
	
	public function cambiar($id)
	{
		$d['controlador'] = $this->router->class;
		
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$permiso = $this->moguardia->permitido('cambiar');
		if($permiso and (int)$id > 0)
		{
			//Datos de la venta
			$this->load->model('mocomun');
			$d['venta'] = $this->mocomun->venta_data($id);
			
			//Sólo se edita serie B
			if($d['venta']->serie == 'A')
			{
				echo "Esta venta no puede ser editada.";
				exit;
			}
			
			//Datos de productos
			$d['productos'] = $this->mocomun->venta_productos($id);
			
			//Datos del cliente
			$this->load->model('mocliente');
			$this->mocliente->init($d['venta']->cliente);
			$d['cliente'] = $this->mocliente->infogral();
		
			$d['titulo'] = "Cambio de precios de venta [<b>{$d['venta']->folio}</b>]";
			$h['titulo'] = strip_tags($d['titulo']);
			$this->load->view('lay/includes.html', $h);
			$this->load->view('lay/header.html');
			$this->load->view('ventas_cambio.html', $d);
			$this->load->view('lay/footer.html');
		}
		else
		{
			redirect(base_url(). 'ventas/detalle/' . $id . '.html');
		}
	}
	
	public function registro_cambiar()
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		if($this->moguardia->permitido('cambiar'))
		{
			$idB = $this->input->post('id', true);
			$totales = $this->input->post('total', true);
			foreach($totales as $id => $total)
			{
				$e = $this->db->get_where('ventas_productos', array('id' => $id))->row();
				$precio = $total / $e->cantidad;
				$_i1 = $precio / 1.16;
				$iva = $precio - $_i1;
				
				//Subtotal
				$subtotal = $e->cantidad * ($precio - $iva);
				
				//IVA
				$iva_moneda = $e->cantidad * $iva;
				
				//Total
				// $_total = $total[$k];
				
				$pi = array(
					'iva_moneda' => $iva_moneda,
					'precio' => $subtotal / $e->cantidad,
					'subtotal' => $subtotal,
					'total' => $total
				);
				$this->db->where('id', $id)->update('ventas_productos', $pi);
			}
			
			//Reconstruir importe
			$this->db->simple_query("UPDATE ventas v SET importe = (SELECT SUM(total) FROM ventas_productos WHERE venta = v.id) WHERE id = {$idB}");
			redirect(path().$this->router->class.'/detalle/'.$idB.suffix());
		}
	}
}