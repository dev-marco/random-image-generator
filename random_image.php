<?php

	// NOTE: this code is optimized for understanding
	// if you want performance, feel free to edit and make your own version
	// Created by Marco: marcoantonio (at) dcc (dot) ufmg (dot) com (dot) br

	// Custom variables

	// Min allowed width
	$min_width = 1;
	// Max allowed width
	$max_width = 1000;
	// Min allowed height
	$min_height = 1;
	// Max allowed height
	$max_height = 1000;
	// Max allowed antialias
	// WARNING: Memory usage will increase very fast with values > 3
	$max_antialias = 3;

	// Min total of objects printed in the image
	$min_objects = 100;
	// Max total of objects printed in the image
	$max_objects = 150;
	// Max side of the polygons
	$max_sides = 8;

	// Default width (when no width is specified)
	$default_width = 200;
	// Default height (when no height is specified)
	$default_height = 200;
	// Default antialias (when no antialias is specified)
	$default_antialias = 1;

	// Copy default width to main width variable
	$width = $default_width;
	// Copy default height to main height variable
	$height = $default_height;
	// Copy default antialias to main antialias variable
	$antialias = $default_antialias;

	// If width is sent via get
	if (isset($_GET['width'])) {
		// Fixed width to the range from $min_width to $max_width
		$width = max(min($max_width, $_GET['width']), $min_width);
	}

	// If height is sent via get
	if (isset($_GET['height'])) {
		// Fix height to the range from $min_height to $max_height
		$height = max(min($max_height, $_GET['height']), $min_height);
	}

	// If antialias is sent via get
	if (isset($_GET['antialias'])) {
		// Fix antialias to the range from 1 to $max_antialias
		$antialias = max(min($max_antialias, $_GET['antialias']), 1);
	}

	// Copy values of width and height
	$real_width = $width;
	$real_height = $height;

	// If antialias is bigger than one
	if ($antialias > 1) {
		// Resize width
		$width  = round($width  * $antialias);
		// Resize height
		$height = round($height * $antialias);
	}

	// If seed is sent via get
	if (isset($_GET['seed'])) {

		// Copy seed value to variable
		$seed_txt = $_GET['seed'];
	} else {

		// Create an unique seed
		$seed_txt = uniqid(true);
	}

	// Convert the seed to hex (for converting to integer)
	$seed_hex = crc32($seed_txt);

	// Convert seed to integer
	$seed = (int) hexdec($seed_txt);

	// Get last time of modification of this file for caching purposes
	$lastmod = filemtime(__FILE__);

	// Calculate an ETAG from the image for caching purposes
	// I'm using the width, height, seed and antialias arguments separated by pipe because if you change any of these values, the image can change
	$etag = md5(sprintf('%u|%u|%u|%u', $real_width, $real_height, $seed, $antialias));

	// Use cache?
	if (
		// Last modification does not exists
		!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ||
		// Last modification received is different from the one got from the file
		strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) !== $lastmod ||
		// ETAG does not exists
		!isset($_SERVER['HTTP_IF_NONE_MATCH']) ||
		// ETAG is different from the one calculated before
		$_SERVER['HTTP_IF_NONE_MATCH'] !== $etag
	) {

		// Seed the random function with the seed received
		mt_srand($seed);

		// Create a new image
		$img = imagecreatetruecolor($width, $height);

		// Generate the red light of the background (0 means no red, 255 means full red)
		$red = mt_rand(0, 255);
		// Generate the green light of the background (0 means no green, 255 means full green)
		$green = mt_rand(0, 255);
		// Generate the blue light of the background (0 means no blue, 255 means full blue)
		$blue = mt_rand(0, 255);
		// Generate the alpha (0 means full opaque, 127 means invisible)
		$alpha = mt_rand(80, 100);
		// Alocate the color for the background
		$bg_color = imagecolorallocatealpha($img, $red, $green, $blue, $alpha);
		// Insert the background rectangle in the image
		imagefilledrectangle($img, 0, 0, $width, $height, $bg_color);

		// Generate the geometric average (root of the product) for object sizing
		$geometric_avg = floor(sqrt($width * $height));

		// Generate the amount of objects to draw
		$objects = mt_rand($min_objects, $max_objects);

		// Iterate over each object
		for ($i = 0; $i < $objects; $i++) {

			// Generate the red light of the object (0 means no red, 255 means full red)
			$red = mt_rand(0, 255);
			// Generate the green light of the object (0 means no green, 255 means full green)
			$green = mt_rand(0, 255);
			// Generate the blue light of the object (0 means no blue, 255 means full blue)
			$blue = mt_rand(0, 255);
			// Generate the alpha (0 means full opaque, 127 means invisible)
			$alpha = mt_rand(80, 100);
			// Alocate the color for the object
			$obj_color = imagecolorallocatealpha($img, $red, $green, $blue, $alpha);

			// Generate the X coordinate of the center of the object between 0 and the image width
			$center_x = mt_rand(0, $width);
			// Generate the Y coordinate of the center of the object between 0 and the image height
			$center_y = mt_rand(0, $height);
			// Generate the distance to the center each vertex will be
			$center_dist = mt_rand($geometric_avg / 8, $geometric_avg / 4);
			// Calculate number of sides (2 means circle)
			$sides = mt_rand(2, $max_sides);

			// Test if is a circle or a polygon
			if ($sides < 3) {

				// Draw the circle
				imagefilledellipse($img, $center_x, $center_y, $center_dist, $center_dist, $obj_color);
			} else {

				// NOTE: there is some serious trigonometry happening here

				// Create array of points
				$points = array();

				// Calculate the angular difference between two vertex (centered in the $center_x, $center_y)
				$angle_difference = 360 / $sides;

				// Generate some random rotation for the object from 0 to the angular difference
				// NOTE: 0 will draw the same polygon as $angle_difference because they are all regular,
				// so 1 will draw the same as ($angle_difference + 1) and so on...
				$rotate = mt_rand(0, $angle_difference);

				// For each side of the polygon
				for ($j = 0; $j < $sides; $j++) {

					// Calculate the angle in radians (needed for cos and sin)
					// Note: I'm using degrees to make the code easier, for performance consider using radians without conversion
					$angle = deg2rad($rotate + $angle_difference * $j);

					// Calculate the position X of the vertex using polar coordinates ( X + distance * cos(angle) )
					$points[] = $center_x + $center_dist * cos($angle);
					// Calculate the position Y of the vertex using polar coordinates ( Y + distance * sin(angle) )
					$points[] = $center_y + $center_dist * sin($angle);

					// https://en.wikipedia.org/wiki/Polar_coordinate_system
				}

				// Draw the polygon
				imagefilledpolygon($img, $points, $sides, $obj_color);
			}
		}

		// Set border thickness of an outer rectangle
		imagesetthickness($img, 1);

		// Black color
		$black = imagecolorallocate($img, 0, 0, 0);
		// Draw an empty rectangle as image border
		imagerectangle($img, 0, 0, $width - 1, $height - 1, $black);

		// If antialias is bigger than one
		if ($antialias > 1) {

			// Create an image with the real dimensions for resize
			$image_resize = imagecreatetruecolor($real_width, $real_height);
			// Copy the full image resized
			imagecopyresampled($image_resize, $img, 0, 0, 0, 0, $real_width, $real_height, $width, $height);

			// Destroy the old image
			imagedestroy($img);

			// Assign the resized image
			$img = $image_resize;

			// Copy real dimensions
			$width = $real_width;
			$height = $real_height;
		}

		// Indicates that here comes an image as PNG
		header('Content-Type: image/png');
		// Open cache
		header('Cache-Control: public');
		// Insists that it should be cached
		header('Pragma: cache');
		// Insert last modification of this file
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $lastmod) . 'GMT');
		// Insert ETAG
		header('ETag: ' . $etag);

		// Tries to activate gzip via ini_set
		if (ini_set('zlib.output_compression', true) === false) {

			// If it doesn't work, use output buffer
			ob_start('ob_gzhandler');
		}

		// Print the image as png
		imagepng($img);
		// Destroy the image
		imagedestroy($img);

	} else {

		// Indicates that the browser should use the cache
		header('HTTP/1.1 304 Not Modified');

	}

	// End php execution here
	exit;

?>
