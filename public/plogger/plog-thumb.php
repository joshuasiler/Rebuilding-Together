<?php

/* This is mostly an interface for the XML service to get thumbnail URLs
 without having to generate the thumb at the time that the URL is needed. */

require(dirname(__FILE__).'/plog-globals.php');
require(dirname(__FILE__).'/plog-include/plog-functions.php');

/**
* plog-thumb.php interface for XML service
*
* Purpose: Provide a link to a thumbnail for a picture even when the thumbnail
* may not have been generated yet.
*
* @param int id (required)
* @param int type (default 1, any of 1,2,3,4)
*/

if (!isset($_REQUEST['id']) || (intval($_REQUEST['id']) == 0)) exit;
if (!isset($_REQUEST['type'])) $_REQUEST['type'] = 1;

$query = "SELECT `path`, `id` FROM `plogger_pictures` WHERE `id`=".intval($_REQUEST['id']);
$result = run_query($query);
$thumb = mysql_fetch_assoc($result);

$thumb['type'] = intval($_REQUEST['type']);

$path = generate_thumb($thumb['path'], $thumb['id'], $thumb['type']);

// Make sure the generated url and the browser url have the same domain syntax
$browser_www = strpos($_SERVER['HTTP_HOST'], 'www');
$thumb_www = strpos($path, 'www');
if ($browser_www !== $thumb_www) {
	if ($browser_www === false) {
		$path = str_replace('www', '', $path);
	} else {
		$path = str_replace('//', '//www.', $path);
	}
}

close_db();

header('Location: '.$path);
exit;

?>