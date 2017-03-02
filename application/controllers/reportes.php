<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reportes extends CI_Controller {

	//Listado
	public function index()
	{
		$this->load->model('moguardia');
		$this->load->model('moproductos');
		$this->moguardia->isin();
		$d['e'] = 0;
		if($this->moguardia->permitido('consultar'))
		{
			//Inicialización
			$d['controlador'] = $this->router->class;
			$d['permisos'] = array(
				'registrar' => $this->moguardia->permitido('registrar'),
				'cancelar' => $this->moguardia->permitido('cancelar'),
				'eliminar' => $this->moguardia->permitido('eliminar')
			);
			
			//Filtros
			$filtro = trim($this->input->post('filtro', true));
			$fecha_venta1 = trim($this->input->post('fecha_venta1', true));
			$fecha_venta2 = trim($this->input->post('fecha_venta2', true));
			$serie = trim($this->input->post('serie', true));
			$go = (int)$this->input->post('go', true);
			
			//Valores para el modelo
			$w = '';
			if(strlen($filtro) > 0) $w .= " AND (folio LIKE '{$filtro}' OR serie = UPPER('{$filtro}') OR CONCAT(serie, '-', folio) = UPPER('{$filtro}') OR cliente = '{$filtro}' OR nombre LIKE '%{$filtro}%')";
			if(strlen($serie) > 0) $w .= " AND serie = '{$serie}'";
			
			//Filtros de fecha
			if(strlen($fecha_venta1) > 0 and strlen($fecha_venta2) > 0){ $w .= " AND fecha_venta BETWEEN '{$fecha_venta1}' AND '{$fecha_venta2}'"; } else { $fecha_venta1 = date('Y-m-d'); $fecha_venta2 = date('Y-m-d'); }
			
			//Todos los productos
			$d['todos'] = $this->moproductos->productos_todos();
			
			//Líneas
			$d['lineas'] = array();
			$lineas = $this->moproductos->lineas();
			foreach($lineas as $l)
			{
				$d['lineas'][] = array(
					'nombre' => $l,
					'columnas' => count($this->moproductos->productos_en_linea($l))
				);
			}
			
			//Obtener ventas
			$ventas = array();
			if($go > 0)
			{
				$s = "SELECT
							v.id,
							v.serie,
							v.folio,
							c.nombre,
							c.direccion,
							v.fecha_venta
							FROM
							ventas v
							INNER JOIN clientes c ON v.cliente = c.id
							WHERE v.status = 0 AND v.serie IN ('" . implode("','", $this->moguardia->series()) . "') {$w}";
				$ventas = $this->db->query($s)->result_array();
				foreach($ventas as $k => $r)
				{
					foreach($d['todos'] as $p)
					{
						$s = "SELECT SUM(v.cantidad) cantidad, v.registro FROM ventas_productos AS v
									INNER JOIN productos AS p ON v.producto = p.id
									WHERE v.venta = {$r['id']} AND p.categoria = {$p->id} GROUP BY p.categoria, v.registro";
						
						// SELECT cantidad, registro FROM ventas_productos WHERE venta = {$r['id']} AND producto = {$p->id}";
						// echo $s."\n";
						$q = $this->db->query($s);
						if($q->num_rows() > 0)
						{
							$productos = $q->result_array();
							$ventas[$k]['productos'][$p->id] = $productos;
						}
					}
				}
			}
			
			$d['registros'] = $ventas;
			
			//Info del usuario (Obtener serie)
			$d['series'] = $this->moguardia->series();
			
			//Reasignar valores para su devolución
			$d['f'] = array(
				'filtro' => $filtro,
				'fecha_venta1' => $fecha_venta1,
				'fecha_venta2' => $fecha_venta2,
				'serie' => $serie
			);
		}
		else
		{
			$d['e']++;
			$d['msg'] = 'No cuenta con los permisos suficientes para el acceso a este módulo.';
		}
		
		$h['titulo'] = "Reporte de ventas";
		$d['titulo'] = $h['titulo'];
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('reporte.html', $d);
		$this->load->view('lay/footer.html');
	}
	
	//Exportar a excel
	function exportar()
	{
		$d['tabla'] = trim($this->input->post('tabla', true));
		$f = trim($this->input->post('fecha', true));
		$this->db->query('SET lc_time_names = "es_MX"');
		$d['fecha'] = $this->db->query("SELECT UPPER(DATE_FORMAT('{$f}', '%d de %M de %Y')) f")->row()->f;
		
		// $d['tabla'] = str_replace('class="text-right"', 'align="right"', $d['tabla']);
		$d['tabla'] = str_replace('56', '52', $d['tabla']);
		$d['tabla'] = preg_replace('/<\/?a[^>]*>/','', $d['tabla']);
		
		$h['titulo'] = "Reporte de ventas";
		$d['titulo'] = $h['titulo'];
		$this->load->view('lay/includes.html', $h);
		$this->load->view('reporte_export.html', $d);
		$this->load->view('lay/footer.html');
	}
}