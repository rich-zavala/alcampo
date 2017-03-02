<?php
class Mocliente extends CI_Model
{
	var $idcli = 0;
	var $fecha = '';
	function __construct()
	{
		parent::__construct();
	}
	
	function init($cli, $fecha = '')
	{
		$this->idcli = intVal($cli);
		$this->fecha = ($fecha != '') ? $fecha : date('Y-m-d');
	}
	
	function infogral()
	{
		$q = $this->db->get_where('clientes', array( 'id' => $this->idcli ));
		if($q->num_rows() > 0)
		{
			$r = $q->row_array();
			
			//Formatear su dirección
			$r['direccion_formateada'] = nl2br($r['direccion']);
			
			//Obtener sus productos
			$r['productos'] = array();
			foreach($this->productos() as $p)
			{
				if($p['disponible'] > 0) $r['productos'][] = $p;
			}
			
			return $r;
		}
		else
		{
			return 0;
		}
	}
	
	function productos()
	{
		$productos = array();
		foreach($this->db->get_where('clientes_productos', array( 'cliente' => $this->idcli ))->result_array() as $r)
		{
			//Obtener más información de este producto
			$info = $this->db->get_where('categorias', array( 'id' => $r['categoria'] ))->row_array();
			$este_producto = array_merge($r, $info);
			$este_producto['disponible'] = $this->producto_tope($r['registro']);
			$productos[] = $este_producto;
		}
		return $productos;
	}
	
	function producto_tope($r)
	{
		$fecha = explode('-', $this->fecha);
		$ano = $fecha[0];
		$mes = $fecha[1];
		//Calcular los topes de consumo que le quedan...
		$s = "SELECT IFNULL((
					SELECT cp.tope - SUM(vp.cantidad) FROM
					clientes_productos AS cp
					INNER JOIN productos AS p ON cp.categoria = p.categoria
					INNER JOIN ventas_productos AS vp ON p.id = vp.producto AND vp.registro = cp.registro
					INNER JOIN ventas AS v ON vp.venta = v.id AND cp.cliente = v.cliente AND v.serie = 'A'
					WHERE
					cp.cliente = {$this->idcli} AND cp.registro = '{$r}' AND v.status = 0 AND
					CASE tope
						WHEN 'anual' THEN v.fecha_venta BETWEEN '{$ano}-01-01' AND '{$ano}-12-31'
						ELSE v.fecha_venta BETWEEN '{$ano}-{$mes}-01' AND '{$ano}-{$mes}-31'
					END
					GROUP BY
					cp.registro), (SELECT tope FROM clientes_productos WHERE cliente = {$this->idcli} AND registro = '{$r}')) disponible";
					// echo $s;
		$disponible = (int)$this->db->query($s)->row()->disponible;
		return $disponible;
	}
	
	function imagenes()
	{
		$imagenes = array();
		$path = 'r_/expedientes/' . $this->idcli;
		@mkdir($path);
		$this->load->helper('file');
		$files = get_filenames($path);
		if(count($files) > 0)
		{
			foreach($files as $img)
			{
				if(substr_count($img, '_t') > 0) $imagenes[] = $path . '/'. $img;
			}
		}
		// foreach($this->db->get_where('clientes_expedientes', array( 'cliente' => $this->idcli ))->result() as $r) $imagenes[] = $r;
		return $imagenes;
	}
}