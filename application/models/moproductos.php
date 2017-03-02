<?php
class Moproductos extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	function lineas()
	{
		$data = array();
		$this->db->order_by('linea', 'ASC');
		$this->db->group_by('linea');
		$q = $this->db->get('categorias');
		if($q->num_rows() > 0)
		{
			foreach($q->result_array() as $l) $data[] = $l['linea'];
			return $data;
		}
		else
		{
			return $data;
		}
	}
	
	function linea_categorias($linea)
	{
		$data = array();
		$this->db->order_by('descripcion', 'ASC');
		$q = $this->db->get_where('categorias', array('linea' => $linea));
		if($q->num_rows() > 0)
		{
			return $q->result_array();
		}
		else
		{
			return $data;
		}
	}
	
	function por_linea()
	{
		$r = array();
		foreach($this->lineas() as $k => $l)
		{
			$r[$k]['linea'] = $l;
			foreach($this->linea_categorias($l) as $p) $r[$k]['categorias'][] = $p;
		}
		return $r;
	}

	function producto_info($id)
	{
		$s = "SELECT c.linea linea, c.descripcion categoria_descripcion, p.*
					FROM productos p INNER JOIN categorias c ON p.categoria = c.id
					WHERE p.id = {$id}";
		$info = $this->db->query($s)->row();
		return $info;
	}
	
	function productos_todos()
	{
		$s = "SELECT c.linea linea, c.descripcion categoria_descripcion, p.*
					FROM productos p INNER JOIN categorias c ON p.categoria = c.id
					ORDER BY c.linea DESC, p.descripcion ASC";
		$this->db->order_by('orden', 'ASC');
		$this->db->order_by('descripcion', 'ASC');
		$q = $this->db->get('categorias');
		// $q = $this->db->query($s);
		if($q->num_rows() > 0)
		{
			return $q->result();
		}
		else
		{
			return array();
		}
	}
	
	function productos_en_linea($linea)
	{
		$this->db->order_by('orden', 'ASC');
		$q = $this->db->get_where('categorias', array( 'linea' => $linea ));
		if($q->num_rows() > 0)
		{
			return $q->result();
		}
		else
		{
			return array();
		}
	}
	
	
	/*function categoria_productos($categoria)
	{
		$data = array();
		$this->db->order_by('descripcion', 'ASC');
		$q = $this->db->get_where('productos', array('categoria' => $categoria));
		if($q->num_rows() > 0)
		{
			return $q->result_array();
		}
		else
		{
			return $data;
		}
	}
	
	function categoria_productos($categoria)
	{
		$data = array();
		$this->db->order_by('descripcion', 'ASC');
		$this->db->group_by('id');
		$q = $this->db->get_where('productos', array('categoria' => $categoria));
		if($q->num_rows() > 0)
		{
			foreach($q->result_array() as $l) $data[] = $l;
			return $data;
		}
		else
		{
			return array();
		}
	}*/
	
	/*function categorias_por_linea() //Agrupación de lineas y sus categorías
	{
		$r = array();
		foreach($this->lineas() as $k => $l)
		{
			$r[$k]['linea'] = $l;
			foreach($this->productos_en_linea($l) as $p) $r[$k]['productos'][] = $p;
		}
		return $r;
	}*/
	
	/*function catalogo_todos()
	{
		$this->db->order_by('linea', 'DESC');
		$this->db->order_by('descripcion', 'ASC');
		$q = $this->db->get('catalogo');
		if($q->num_rows() > 0)
		{
			return $q->result();
		}
		else
		{
			return array();
		}
	}

	function catalogo_info($id)
	{
		$info = $this->db->get_where( 'catalogo', array( 'id' => $id ) )->row();
		return $info;
	}
	
	function productos_todos()
	{
		$this->db->order_by('linea', 'DESC');
		$this->db->order_by('descripcion', 'ASC');
		$q = $this->db->get('productos');
		if($q->num_rows() > 0)
		{
			return $q->result();
		}
		else
		{
			return array();
		}
	}
	
	function categorias_en_linea($linea)
	{
		$this->db->order_by('descripcion', 'ASC');
		$q = $this->db->get_where('productos', array( 'linea' => $linea ));
		if($q->num_rows() > 0)
		{
			return $q->result();
		}
		else
		{
			return array();
		}
	}*/
}