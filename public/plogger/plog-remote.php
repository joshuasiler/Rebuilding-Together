<?php
/*
Support for Gallery remote protocol, details at
http://gallery.menalto.com/modules.php?op=modload&name=GalleryDocs&file=index&page=gallery-remote.protocol.php

Written by Anti Veeranna (http://masendav.com)
*/

error_reporting(E_ALL);

require_once(dirname(__FILE__).'/plog-load-config.php');
include_once(PLOGGER_DIR.'plog-admin/plog-admin-functions.php');

define('DEBUG', 0);
$debug_msgs = '';
define('GR_SERVER_VERSION', '2.14');

define('GR_STAT_SUCCESS', 0);
define('GR_STAT_PROTO_MAJ_VER_INVAL', 101);
define('GR_STAT_PROTO_MIN_VER_INVAL', 102);
define('GR_STAT_PROTO_VER_FMT_INVAL', 103);
define('GR_STAT_PROTO_VER_MISSING', 104);
define('GR_STAT_PASSWORD_WRONG', 201);
define('GR_STAT_LOGIN_MISSING', 202);
define('GR_STAT_UNKNOWN_CMD', 301);
define('GR_STAT_NO_ADD_PERMISSION', 401);
define('GR_STAT_NO_FILENAME', 402);
define('GR_STAT_UPLOAD_PHOTO_FAIL', 403);
define('GR_STAT_NO_WRITE_PERMISSION', 404);
define('GR_STAT_NO_CREATE_ALBUM_PERMISSION', 501);
define('GR_STAT_CREATE_ALBUM_FAILED', 502);

class response {
	function response() {
		$this->keys = array();
		$this->keys['server_version'] = GR_SERVER_VERSION;
	}

	function set_key($key, $value) {
		$this->keys[$key] = $value;
	}

	function write() {
		print "#__GR2PROTO__\n";
		foreach($this->keys as $key => $val) print "${key}=${val}\n";
	}
}

function get_album_by_name($name) {
	$sqlAlbum = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."albums` WHERE name = '".mysql_real_escape_string($name)."'";
	$resultAlbum = run_query($sqlAlbum);
	return mysql_fetch_assoc($resultAlbum);
}

function login($user, $password) {
	global $response;
	global $config;

	if (($user == $config['admin_username']) && (md5($password) == $config['admin_password'])) {
		$response->set_key('status', GR_STAT_SUCCESS);
		$response->set_key('status_text', 'Login successful');
	} else {
		$response->set_key('status', GR_STAT_PASSWORD_WRONG);
		$response->set_key('status_text', 'Login failed');
	}
}

function list_albums() {
	global $config;

	// On first level we show collections
	$sqlCollections = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."collections` ORDER BY `name` ASC";
	$resultCollections = run_query($sqlCollections);
	$albums = $parents = array();
	$albums[1] = array(
	'name' => 'Plogger',
	'title' => $config['gallery_name'],
	'summary' => '1',
	'parent' => 0,
	// No pictures here
	'perms.add' => 'false',
	'perms.write' => 'false',
	'perms.del_item' => 'false',
	'perms.del_alb' => 'false',
	// But albums can be created
	'perms.create_sub' => 'true',
	);
	$i = 2;

	while($rowCollection = mysql_fetch_assoc($resultCollections)) {
		$id = $rowCollection['id'];
		$description = $rowCollection['description'];
		$name = $rowCollection['name'];
		if (empty($description)) {
			$description = ' ';
		}
		if (empty($name)) {
			$name = 'no name';
		}

		$albums[$i] = array(
		//'name' => $rowCollection['name'],
		//'name' => $rowCollection['description'],
		'name' => $name,
		'title' => $name,
		'id' => $id,
		// There is no usable summary
		'summary' => '',
		// Collections are on the first level
		'parent' => 1,
		// Images cannot be placed in the collections
		'perms.add' => 'false',
		'perms.write' => 'false',
		'perms.del_item' => 'false',
		'perms.del_alb' => 'false',
		// But albums can be created
		'perms.create_sub' => 'true',
		);
		$parents[$id] = $i;
		$i++;
	}

	$sqlAlbum = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."albums` ORDER BY `name` ASC";
	$resultAlbum = run_query($sqlAlbum);
	while ($rowAlbum = mysql_fetch_assoc($resultAlbum)) {
		$id = $rowAlbum['id'];
		$parent_id = $parents[$rowAlbum['parent_id']];
		$albums[$i] = array(
		'name' => $rowAlbum['name'],
		'title' => $rowAlbum['name'],
		'summary' => $rowAlbum['description'],
		// Albums belong to a collection
		'parent' => $parent_id,
		'resize_size' => 480,
		'thumb_size' => 240,
		// No acl system either, if the user is logged in, then they can add/change images
		'perms.add' => 'true',
		'perms.write' => 'true',
		'perms.del_item' => 'true',
		// Albums cannot be nested
		'perms.create_sub' => 'false',
		);
		$i++;
	}

	$i = 1;

	global $response;

	$response->set_key('status', GR_STAT_SUCCESS);
	// galleryadd.pl looks for this exact status text, other clients do not care
	$response->set_key('status_text', 'Fetch albums successful.');

	foreach($albums as $id => $data) {
		unset($data['id']);
		foreach($data as $key => $val) {
			$response->set_key("album.${key}.${i}", $val);
		}
		$i++;
	}
	$response->set_key('album_count', $i);
	$response->set_key('can_create_root', 'no');
}

function list_images($albumname) {
	global $response;
	$response->set_key('status', GR_STAT_SUCCESS);
	$response->set_key('status_text', 'List of images');

	if (empty($albumname)) {
		$albumname = 'Plogger';
	}

	$albuminfo = get_album_by_name($albumname);
	$i = 0;

	if ($albuminfo) {
		$sqlPictures = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE parent_album = ".intval($albuminfo['id']);
		$resultAlbum = run_query($sqlPictures);
		while ($rowAlbum = mysql_fetch_assoc($resultAlbum)) {
			$response->set_key("image.name.${i}", $rowAlbum['path']);
			//print "image.raw_width.0=400\n";
			//print "image.raw_height.0=400\n";
			//print "image.raw_filesize.0=40000\n";
			$thumbname = 'plog-content/thumbs/'.$rowAlbum['id'].'-'.basename($rowAlbum['path']);
			$response->set_key("image.thumbName.${i}", $thumbname);
			$i++;
		}
	}

	$response->set_key('image_count', $i);
	$server = 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']).'/';
	$response->set_key('baseurl', $server);
}

function gr_add_album($parent, $name, $description) {
	// Parent is the name of the collection
	$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."collections` WHERE name = '".mysql_real_escape_string($parent)."'";
	$result = run_query($query);

	$row = mysql_fetch_assoc($result);

	if (empty($name)) {
		$name = 'no name';
	}

	if (empty($description)) {
		$description = 'no description';
	}

	$parent_id = $row['id'];

	$result = add_album($name, $description, $parent_id);

	global $response;

	if (0 == $result['id']) {
		$response->set_key('status', GR_STAT_CREATE_ALBUM_FAILED);
		$response->set_key('status_text', 'Could not create album');
	} else {
		$response->set_key('status', GR_STAT_SUCCESS);
		$response->set_key('status_text', 'Album created');
	}
}

function add_image($album, $filename, $caption) {
	$filedat = $_FILES['userfile'];
	$albuminfo = get_album_by_name($album);
	$src = $filedat['tmp_name'];
	$result = add_picture($albuminfo['id'], $_FILES['userfile']['tmp_name'], $_FILES['userfile']['name'], $caption);

	global $debug_msgs;
	$debug_msgs .= print_r($result, true);

	// And this is the place where I need the image data

	global $response;
	if ($result['picture_id'] === false) {
		$response->set_key('status', GR_STAT_UPLOAD_PHOTO_FAIL);
		$response->set_key('status_text', 'Add photo failed.');
	} else {
		$response->set_key('status', GR_STAT_SUCCESS);
		// galleryadd.pl looks for this exact status text and fails if it doesn't find it
		$response->set_key('status_text', 'Add photo successful.');
	}
}

header('Content-type: text/plain');
$cmd = isset($_POST['cmd']) ? $_POST['cmd'] : '';

if (DEBUG) {
	$fd = fopen('debug.txt', 'a');
	fwrite($fd, print_r($_POST, true));
	fwrite($fd, print_r($_FILES, true));
	fwrite($fd, print_r($debug_msgs, true));
	fclose($fd);
}

$response = new response();

switch($cmd) {
	case 'login':
		login($_POST['uname'], $_POST['password']);
	break;

	case 'fetch-albums':
		list_albums();
	break;

	case 'fetch-album-images';
		list_images($_POST['set_albumName']);
	break;

	case 'add-item':
		add_image($_POST['set_albumName'], $_FILES['userfile']['name'], $_POST['caption']);
	break;

	case 'new-album':
		// There is a title field as well, but since Plogger doesn't use it, we drop it
		gr_add_album($_POST['set_albumName'], $_POST['newAlbumTitle'], $_POST['newAlbumDesc']);
	break;

	default:
	$response->set_key('status', GR_STAT_UNKNOWN_CMD);
	$response->set_key('status_text', 'Unknown command.');
}

$response->write();
close_db();

?>