<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inyector extends CI_Controller {
		
	public function index($error = '')
	{
		$this->db->simple_query('TRUNCATE clientes');
		$this->db->simple_query('TRUNCATE clientes_productos');
		
		$calibres = array(
			1 => '_22M',
			2 => '_25',
			3=> '_32',
			4 => '_38',
			5 => '_38SPL',
			6 => '_12',
			7 => '_16',
			8 => '_20',
			9 => '_22',
			10 => '_28',
			11 => '_410'
		);
		$this->db->order_by('nombre');
		foreach($this->db->get('inyector')->result() as $r)
		{
			//Insertar el cliente
			$i = array(
				'nombre' => $r->nombre,
				'direccion' => $r->direccion
			);
			$this->db->insert('clientes', $i);
			$id = $this->db->insert_id();
			
			//Insertar los productos
			foreach($calibres as $producto => $columna)
			{
				foreach(explode(',', $r->$columna) as $registro)
				{
					if(strlen(trim($registro)) > 0)
					{
						$i = array(
							'cliente' => $id,
							'producto' => $producto,
							'registro' => trim($registro),
							'tope' => 100,
							'tipo' => 'mensual'
						);
						$this->db->insert('clientes_productos', $i);
					}
				}
			}
		}
	}
}