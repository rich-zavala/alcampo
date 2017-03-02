<?php
//Administra las sesiones de Lola
class Mocomun extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	function listado($tabla, $where, $orden, $limit)
	{
		$this->db->query('SET lc_time_names = "es_MX"');
	
		foreach($where as $w) $this->db->or_where($w);
		$this->db->where('status > ', '-1');
		$this->db->order_by($orden[0], $orden[1]);
		$total = $this->db->get($tabla)->num_rows();
		
		foreach($where as $w) $this->db->or_where($w);
		$this->db->where('status > ', '-1');
		$this->db->order_by("{$orden[0]} {$orden[1]}");
		$q = $this->db->get($tabla, $limit[0], $limit[1]);
		
		return array(
			'resultados' => $q->result(),
			'total' => $total
		);
	}
	
	function pagination($controlador, $mostrar, $total)
	{		
		$this->load->library('pagination');
		$config = array(
			'base_url' => path().$controlador.'/index/',
			'total_rows' => $total,
			'per_page' => $mostrar,
			'use_page_numbers' => true,
			'full_tag_open' => '<hr /><ul class="pagination marginTop10">',
			'full_tag_close' => '</ul>',
			'cur_tag_open' => '<li class="disabled"><a href="#">',
			'cur_tag_close' => '</a></li>',
			'num_tag_open' => '<li>',
			'num_tag_close' => '</li>',
			'next_tag_open' => '<li>',
			'next_tag_close' => '</li>',
			'prev_tag_open' => '<li>',
			'prev_tag_close' => '</li>',
			'first_tag_open' => '<li>',
			'first_tag_close' => '</li>',
			'first_link' => utf8_encode('Primero'),
			'last_tag_open' => '<li>',
			'last_tag_close' => '</li>',
			'last_link' => utf8_encode('Último')
		);
		$this->pagination->initialize($config);
		return $this->pagination->create_links();
	}

	function venta_data($id)
	{		
		//Datos de la venta
		$this->db->query('SET lc_time_names = "es_MX"');
		$s = "SELECT
					id, serie, folio n, CONCAT(serie, '-', v.folio) folio, v.cliente,
					DATE_FORMAT(fecha_registro, '%d/%M/%Y - %H:%i') AS fecha_registro,
					DATE_FORMAT(fecha_venta, '%d/%M/%Y') AS fecha,
					DATE_FORMAT(fecha_venta, '%d de %M del %Y') AS fecha_c,
					v.importe, v.status, u.nombre usuario
					FROM ventas AS v INNER JOIN usuarios AS u ON v.user = u.user WHERE v.id = {$id}";
		return $this->db->query($s)->row();	
	}
	
	function venta_productos($id)
	{
		$resultado = array();
		$CI =& get_instance();
		$CI->load->model('moproductos');
		$r = $this->db->get_where('ventas_productos', array('venta' => $id))->result();
		foreach($r as $k => $v)
		{
			$info = $CI->moproductos->producto_info($v->producto);
			$arreglo = array_merge(json_decode(json_encode($info), true), json_decode(json_encode($v), true));
			$resultado[] = $arreglo;
		}
		return $resultado;
	}
	
	function venta_existe($s, $f)
	{
		return $this->db->query("SELECT IFNULL((SELECT id FROM ventas WHERE serie = '{$s}' AND folio = '{$f}'), 0) id")->row()->id;
	}
	
	function venta_copia($id)
	{
		if($this->db->query("SELECT serie FROM ventas WHERE id = {$id}")->row()->serie == 'B')
		{
			echo "Error en folio de copia.";
			exit;
		}
		else
		{
			$s = "INSERT INTO ventas select NULL, 'B', folio, cliente, user, fecha_venta, NOW(), importe, 0 FROM ventas WHERE id = {$id}";
			$this->db->simple_query($s);
			$idB = $this->db->insert_id();
			
			$original = $this->db->get_where('ventas', array('id' => $id))->row();
			$this->db->insert('serie_B', array( 'serie' => 'B', 'id' => $original->folio ));
			
			$s = "INSERT INTO ventas_productos SELECT NULL, {$idB}, producto, registro, cantidad, iva, iva_moneda, precio, subtotal, total FROM ventas_productos WHERE venta = {$id}";
			$this->db->simple_query($s);
			return $idB;
		}
	}
}