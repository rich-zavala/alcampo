<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Productos extends CI_Controller {

	//Listado
	public function index()
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		if($this->moguardia->permitido('consultar'))
		{
			$d['controlador'] = $this->router->class;
			$d['exito'] = isset($_GET['exito']) ? 1 : 0;
		
			/*Obtener líneas de productos*/
			$this->load->model('moproductos');
			$d['lineas'] = $this->moproductos->lineas();
			$cats = array();
			foreach($d['lineas'] as $linea) $cats['l' . md5($linea)] = $this->moproductos->linea_categorias($linea);
			$d['categorias'] = json_encode($cats);
			
			$d['productos'] = '';
			$s = "SELECT p.id, p.categoria, p.descripcion, precio, total, CONCAT('l',MD5(linea)) linea, 0 vendidos
						FROM productos AS p INNER JOIN categorias AS c ON p.categoria = c.id ORDER BY categoria, p.descripcion";
			$q = $this->db->query($s);
			if($q->num_rows()) $d['productos'] = json_encode($q->result_array());
			
		}
		else
		{
			$d['e']++;
			$d['msg'] = 'No cuenta con los permisos suficientes para el acceso a este módulo.';
		}
		
		$h['titulo'] = "Productos";
		$d['titulo'] = $h['titulo'];
		$this->load->view('lay/includes.html', $h);
		$this->load->view('lay/header.html');
		$this->load->view('productos.html', $d);
		$this->load->view('lay/footer.html');
	}
	
	//Actualización
	public function registro()
	{
		$this->load->model('moguardia');
		$this->moguardia->isin();
		$msg = array();
		if($this->moguardia->permitido('consultar'))
		{
			$categoria = $this->input->post('categoria');
			$descripcion = $this->input->post('descripcion');
			$id = $this->input->post('id');
			$total = $this->input->post('total');
			
			foreach($total as $k => $t)
			{
				$i = array(
					'categoria' => $categoria[$k],
					'descripcion' => $descripcion[$k],
					'total' => $t,
					'iva' => 16
				);
				if((int)$id[$k] == 0)
				{
					$this->db->insert('productos', $i);
					$id[$k] = $this->db->insert_id();
				}
				else
				{
					$this->db->where('id', $id[$k]);
					$this->db->update('productos', $i);
				}
			}
			$this->db->simple_query('UPDATE productos SET precio = ROUND(total / ( 1 + (16 / 100) ), 2)');
			
			//Eliminar productos que no están en el formulario
			$s = "DELETE FROM productos WHERE id NOT IN (" . implode(',', $id) . ")";
			$this->db->simple_query($s);
			
			$url = path().$this->router->class.suffix().'?exito';
			redirect($url);
		}
		else{ echo 654654; }
	}
}