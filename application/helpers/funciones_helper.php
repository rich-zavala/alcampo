<?php
function path()
{
  $ci = & get_instance();
	return $ci->config->item('base_url');
}

function suffix()
{
  $ci = & get_instance();
	return $ci->config->item('url_suffix');
}

function active($x1, $x2)
{
	return ($x1 == $x2) ? 'active' : '';
}

function p($a)
{
	echo '<pre>';
	print_r($a);
	echo '</pre>';
}

function selected($x1, $x2)
{
	if(is_array($x1))
	{
		foreach($x1 as $x)
		{
			if($x == $x2)
			{
				return 'selected = "selected"';
			}
		}
	}
	else if($x1 == $x2)
	{
		return 'selected = "selected"';
	}
}

function money($num)
{
	return number_format($num, 2);
}