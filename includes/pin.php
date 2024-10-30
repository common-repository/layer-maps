<?php

define('PLUGIN_PATH', dirname(dirname(__FILE__)));
define('UPLOADS_PATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/uploads/layermaps-pins');

$colour = $_GET['colour'];
$number = (int)$_GET['number'];

$accepted_colours = array(
	'black',
	'blue',
	'green',
	'grey',
	'indigo',
	'orange',
	'pink',
	'purple',
	'red',
	'turquoise',
	'yellow',
	'maroon',
	'navyblue',
	'light_blue',
	'light_green',
	'light_red',
	'light_white',
);

function create_file($colour, $number, $accepted_colours) {

	if(in_array($colour, $accepted_colours) && $number > 0) {
		$base_icon = PLUGIN_PATH . '/assets/images/icons/';
		$font_path = PLUGIN_PATH . '/assets/fonts/verdanab.ttf';

		$text = $number;
		$template = $base_icon . $colour . '_marker.png';
		$font_colour = 0xFFFFFF; // Black

		if(stristr($colour, 'light') !== false) {
			$font_colour = 0x000000; // White
		}

		if($text >= 1 && $text <= 9) {
			$font_size = 12;
			$offset_x = 1;
		}
		elseif($text >= 10 && $text <= 99) {
			$font_size = 10;
			$offset_x = 1;
		}
		else {
			$font_size = 8;
			$offset_x = 0;
		}

		$gdimage = imagecreatefrompng($template);
		imagesavealpha($gdimage, true);

		list($x0, $y0, , , $x1, $y1) = imagettfbbox($font_size, 0, $font_path, $text);
		$imwide = imagesx($gdimage);

		$imtall = imagesy($gdimage) - 4;

		$bbwide = abs($x1 - $x0);
		$bbtall = abs($y1 - $y0);

		$tlx = ($imwide - $bbwide) >> 1; $tlx += $offset_x;
		$tly = ($imtall - $bbtall) >> 1; $tly -= 1;
		$bbx = $tlx - $x0;
		$bby = $tly + $bbtall - $y0;

		imagettftext($gdimage, $font_size, 0, $bbx, $bby, $font_colour, $font_path, $text);

		$filename = $colour . '_' . $text . '_marker.png';

		if(!file_exists(UPLOADS_PATH)) {
			mkdir(UPLOADS_PATH, 0755, true);
		}

		if(file_exists(UPLOADS_PATH)) {
			$path = UPLOADS_PATH . '/' .  $filename;

			if(imagepng($gdimage, $path)) {
				return $path;
			}
		}

	}

	return false;
}

if(in_array($colour, $accepted_colours) && $number > 0) {

	$file = UPLOADS_PATH . '/' . $colour . '_' . $number . '_marker.png';

	if(file_exists($file)) {
		header('Content-type: image/png');
		readfile($file);
	}
	else {
		header('Content-type: image/png');

		$create_file = create_file($colour, $number, $accepted_colours);

		if($create_file) {
			$file = $create_file;
		}
		else {
			$file = PLUGIN_PATH . '/assets/images/icons/black_marker.png';
		}

		readfile($file);
	}
}

