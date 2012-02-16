<?php
/**
* Generate a CAPTCHA and display the image
*
* @author Constantinos Neophytou
* @link http://www.cneophytou.com/
* @license Creative Commons Attribution-Noncommercial-Share Alike 3.0 License
* {@link http://creativecommons.org/licenses/by-nc-sa/3.0/}
**/

/**
* Initialize page
**/

include_once(dirname(dirname(__FILE__)).'/plog-config.php');
// Set the session.save_path if user defined in plog-config
if (defined('PLOGGER_SESSION_SAVE_PATH') && PLOGGER_SESSION_SAVE_PATH != '') {
	session_save_path(PLOGGER_SESSION_SAVE_PATH);
}

session_start();

header('Content-Type: image/png');
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private', false);
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

create_image();
exit;

/**
* Generate a random string, and create a CAPTCHA image out of it
*/
function create_image() {
	// Generate pronouncable pass
	$pass = genPassword(5, 6);
	$font = './captcha.ttf';
	$maxsize = 50;
	$sizeVar = 25;
	$rotate = 20;
	$bgcol = 50; // + 50
	$bgtextcol = 80; // + 50
	$textcol = 205; // + 50

	// Remember the pass
	$_SESSION['captcha'] = $pass;

	// Calculate dimentions required for pass
	$box = @imageTTFBbox($maxsize, 0, $font, $pass);
	$minwidth = abs($box[4] - $box[0]);
	$minheight = abs($box[5] - $box[1]);

	// Allow spacing for rotating letters
	$width = $minwidth + 100;
	$height = $minheight + rand(5,15); // give some air for the letters to breathe
	// Create initial image
	$image = ImageCreatetruecolor($width, $height);

	if (function_exists('imageantialias')) {
		imageantialias($image, true);
	}
	// Define background color - never the same, close to black
	$clr_black = ImageColorAllocate($image, rand($bgcol, $bgcol+30),
	rand($bgcol, $bgcol+30), rand($bgcol, $bgcol+30));
	imagefill($image, 0, 0, $clr_black);

	// Calculate starting positions for letters
	$x = rand(10, 25); //($width / 2) - ($minwidth / 2);
	$xinit = $x;
	$y = ($minheight-abs($box[1])) + (($height - $minheight) / 2);

	// Fill the background with big letters, colored a bit lightly, to vary the bg.
	$bgx = $x / 2;
	$size = rand($maxsize - 10, $maxsize);
	for($i = 0; $i < strlen($pass); $i++) {
		// Modify color a bit
		$clr_white = ImageColorAllocate($image, rand($bgtextcol, $bgtextcol+50), rand($bgtextcol, $bgtextcol+50), rand($bgtextcol, $bgtextcol+50));
		$angle = rand(0-$rotate, $rotate);
		$letter = substr($pass, $i, 1);
		imagettftext($image, $size*2, $angle, $bgx, $y, $clr_white, $font, $letter);
		list($x1, $a, $a, $a, $x2) = @imageTTFBbox($size, $angle, $font, $letter);
		$bgx += abs($x2 - $x1);
	}

	// For each letter, decide a color, decide a rotation, put it on the image,
	// and figure out width to place next letter correctly
	for($i = 0; $i < strlen($pass); $i++) {
		// Modify color a bit
		$clr_white = ImageColorAllocate($image, rand($textcol, $textcol+50), rand($textcol, $textcol+50), rand($textcol, $textcol+50));

		$angle = rand(0-$rotate, $rotate);
		$letter = substr($pass, $i, 1);
		$size = rand($maxsize - $sizeVar, $maxsize);
		$tempbox = @imageTTFBbox($size, $angle, $font, $letter);

		$y = (abs($tempbox[5] - $tempbox[1])) +
		(($height - abs($tempbox[5] - $tempbox[1])) / 2);

		imagettftext($image, $size, $angle, $x, $y, $clr_white, $font, $letter);
		$x += abs($tempbox[4]-$tempbox[0]);
	}
	// Figure out final width (same space at the end as there was at the beginning)
	$width = $xinit + $x;

	// Throw in some lines
	$clr_white = ImageColorAllocate($image, rand(160, 200), rand(160, 200), rand(160, 200));
	imagelinethick($image, rand(0, 10), rand(0, $height / 2), rand($width - 10, $width), rand($height / 2, $height), $clr_white, rand(1, 2));
	$clr_white = ImageColorAllocate($image, rand(160, 200), rand(160, 200), rand(160, 200));
	imagelinethick($image, rand(($width / 2) - 10, $width / 2), rand($height / 2, $height), rand(($width / 2)+ 10, $width),
	rand(0, ($height / 2)), $clr_white, rand(1, 2));

	// Generate final image by cropping initial image to the proper width,
	// which we didn't know till now.
	$finalimage = ImageCreatetruecolor($width, $height);
	if (function_exists('imageantialias')) {
		imageantialias($finalimage, true);
	}
	imagecopy($finalimage, $image, 0, 0, 0, 0, $width, $height);
	// Clear some memory
	imagedestroy($image);

	// Dump image
	imagepng($finalimage);

	// Clear some more memory
	imagedestroy($finalimage);
}

/**
* Draw lines through an image
* @param resource
* @param int
* @param int
* @param int
* @param int
* @param int - the color to use for the line
* @param int - line thickness
* @return bool - true on success
*/
function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1) {
	if ($thick == 1) {
		return imageline($image, $x1, $y1, $x2, $y2, $color);
	}
	$t = $thick / 2 - 0.5;
	if ($x1 == $x2 || $y1 == $y2) {
		return imagefilledrectangle($image, round(min($x1, $x2) - $t),
		round(min($y1, $y2) - $t),
		round(max($x1, $x2) + $t),
		round(max($y1, $y2) + $t),
		$color);
	}
	$k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
	$a = $t / sqrt(1 + pow($k, 2));
	$points = array(
	round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
	round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
	round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
	round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
	);
	imagefilledpolygon($image, $points, 4, $color);
	return imagepolygon($image, $points, 4, $color);
}

/**
* Generate a random, pronouncable password
* Modified to exclude letters which don't show up well in the CAPTCHA
* @link http://www.zend.com/code/codex.php?id=215&single=1
* @author Rival7 {@link http://www.zend.com/code/search_code_author.php?author=Rival7}
* @author Constantinos Neophytou
*/
function genPassword($minlen, $maxlen) {
	srand((double)microtime()*1000000);

	$vowels = array('a', 'e', 'i', 'o', 'u');
	$cons = array('b', 'c', 'd', 'g', 'h', 'j', 'k', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'tr', 'cr', 'br', 'fr', 'th', 'dr', 'ch', 'ph', 'wr', 'st', 'sp', 'sw', 'pr');
	// Removed 'l', 'sl', 'cl'

	$num_vowels = count($vowels);
	$num_cons = count($cons);

	$length = rand($minlen, $maxlen);

	$start = rand(0, 1);
	if ($start) {
		$first = $cons;
		$num_first = $num_cons;
		$second = $vowels;
		$num_second = $num_vowels;
	} else {
		$first = $vowels;
		$num_first = $num_vowels;
		$second = $cons;
		$num_second = $num_cons;
	}

	for($i = 0; $i < $length; $i++) {
		$add = $first[rand(0, $num_first - 1)].$second[rand(0, $num_second - 1)];
		$password .= $add;
		$i += (strlen($add) - 1);
	}

	//$password = substr($password, 0, $length);
	return $password;
}

?>