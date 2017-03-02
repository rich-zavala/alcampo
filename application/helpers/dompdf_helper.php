<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

	function pdf_create($html, $filename, $stream = false, $orientation = '')
	{
		require_once("dompdf/dompdf_config.inc.php");		
		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
		$dompdf->set_base_path(path());
		
		if(strlen($orientation) > 0) $dompdf->set_paper('letter', $orientation);
		$dompdf->render();
		$output = $dompdf->output();
		if($stream)
		{
			$dompdf->stream($filename.".pdf");
		}
		else
		{
			file_put_contents($filename, $output);
		}
	}
?>