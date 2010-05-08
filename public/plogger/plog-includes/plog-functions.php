<?php

if (basename($_SERVER['PHP_SELF']) == basename( __FILE__ )) {
	// ignorance is bliss
	exit();
}

function generate_password($low = 5, $high = 8) {
	$salt = md5(time().crypt('abcdefghkmnpqrstuvwxyz23456789'));
	$src = preg_split('//', $salt, -1, PREG_SPLIT_NO_EMPTY);
	shuffle($src);
	$num = rand(intval($low), intval($high));
	return join('', array_slice($src, 0, $num));
}

function generate_breadcrumb($title = false, $sep = ' &raquo; ') {
	global $config;

	$id = $GLOBALS['plogger_id'];

	if ($title === false) {
		if (!empty($config['gallery_name'])) {
			$title = $config['gallery_name'];
		} else {
			$title = plog_tr('Home');
		}
	}

	$collections_link = '<a href="'.generate_url('collections').'">'.$title.'</a>';
	$collections_name = '<strong>'.$title.'</strong>';

	switch ($GLOBALS['plogger_level']) {
		case 'collection':
			$row = get_collection_by_id($id);
			$collection_name = '<strong>'.SmartStripSlashes($row['name']).'</strong>';

			if ($config['truncate_breadcrumb'] == 'collection') {
				$breadcrumbs = $collections_name;
			} else {
				$breadcrumbs = $collections_link.$sep.$collection_name;
			}

			// Does this ever happen? Collection level + slideshow mode ends in SQL error
			// if ($GLOBALS['plogger_mode'] == 'slideshow') $breadcrumbs .= $sep.'<strong>'.plog_tr('Slideshow');

			break;
		case 'slideshow':
		case 'album':
			$row = get_album_by_id($id);
			$album_name = '<strong>'.SmartStripSlashes($row['name']).'</strong>';
			$album_link = '<a accesskey="/" href="'.generate_url('album', $row['id']).'">'.SmartStripSlashes($row['name']).'</a>';

			$row = get_collection_by_id($row['parent_id']);
			$collection_link = '<a accesskey="/" href="'.generate_url('collection', $row['id']).'">'.SmartStripSlashes($row['name']).'</a>';

			if ($config['truncate_breadcrumb'] == 'album') {
				$breadcrumbs = $collections_name;
			} else if ($config['truncate_breadcrumb'] == 'collection') {
				$breadcrumbs = $collections_link.$sep.$album_name;
			} else {
				$breadcrumbs = $collections_link.$sep.$collection_link.$sep.$album_name;
			}

			if ($GLOBALS['plogger_mode'] == 'slideshow') {
				$breadcrumbs = str_replace($album_name, $album_link.$sep.'<strong>'.plog_tr('Slideshow').'</strong>', $breadcrumbs);
			}

			break;
		case 'picture':
			$row = get_picture_by_id($id);
			$picture_name = '<span id="image_name"><strong>'.SmartStripSlashes(get_caption_filename($row)).'</strong></span>';

			$row = get_album_by_id($row['parent_album']);
			$album_link = '<a accesskey="/" href="'.generate_url('album', $row['id']).'">'.SmartStripSlashes($row['name']).'</a>';

			$row = get_collection_by_id($row['parent_id']);
			$collection_link = '<a accesskey="/" href="'.generate_url('collection', $row['id']).'">'.SmartStripSlashes($row['name']).'</a>';

			if ($config['truncate_breadcrumb'] == 'album') {
				$breadcrumbs = $collections_link.$sep.$picture_name;
			} else if ($config['truncate_breadcrumb'] == 'collection') {
				$breadcrumbs = $collections_link.$sep.$album_link.$sep.$picture_name;
			} else {
				$breadcrumbs = $collections_link.$sep.$collection_link.$sep.$album_link.$sep.$picture_name;
			}

			// Does this ever happen? Picture level + slideshow adds 'Slideshow' to breadcrumbs only
			//if ($GLOBALS['plogger_mode'] == 'slideshow') $breadcrumbs .= $sep.'<strong>'.plog_tr('Slideshow').'</strong>';

			break;
		case 'search':
			if ($config['truncate_breadcrumb'] == 'album') {
				$row = get_album_by_id($id);
				$album_link = '<a accesskey="/" href="'.generate_url('album', $row['id']).'">'.SmartStripSlashes($row['name']).'</a>';
				$breadcrumbs = $album_link.$sep.'<strong>'.plog_tr('Search').'</strong>'.$sep.plog_tr('You searched for').' <strong>'.htmlspecialchars(SmartStripSlashes($_REQUEST['searchterms'])).'</strong>.';
			} else if ($config['truncate_breadcrumb'] == 'collection') {
				$row = get_collection_by_id($id);
				$collection_link = '<a accesskey="/" href="'.generate_url('collection', $row['id']).'">'.SmartStripSlashes($row['name']).'</a>';
				$breadcrumbs = $collection_link.$sep.'<strong>'.plog_tr('Search').'</strong>'.$sep.plog_tr('You searched for').' <strong>'.htmlspecialchars(SmartStripSlashes($_REQUEST['searchterms'])).'</strong>.';
			} else {
				$breadcrumbs = $collections_link.$sep.'<strong>'.plog_tr('Search').'</strong>'.$sep.plog_tr('You searched for').' <strong>'.htmlspecialchars(SmartStripSlashes($_REQUEST['searchterms'])).'</strong>.';
			}
			break;
		case '404':
			$breadcrumbs = $collections_link.$sep.'<strong>'.plog_tr('404 Error - Not Found').'</strong>';
			break;
		default:
			$breadcrumbs = $collections_name;
			break;
	}

	return '<div id="breadcrumb-links">'.$breadcrumbs.'</div>';
}

function generate_title() {
	switch ($GLOBALS['plogger_level']) {
		case 'collection':
			$row = get_collection_by_id($GLOBALS['plogger_id']);

			$breadcrumbs = SmartStripSlashes($row['name']);
			if ($GLOBALS['plogger_mode'] == 'slideshow') {
				$breadcrumbs .= ' &raquo; '.plog_tr('Slideshow');
			}

			break;
		case 'slideshow':
		case 'album':
			$row = get_album_by_id($GLOBALS['plogger_id']);
			$album_name = SmartStripSlashes($row['name']);

			$row = get_collection_by_id($row['parent_id']);

			if ($GLOBALS['plogger_mode'] == 'slideshow') {
				$breadcrumbs = SmartStripSlashes($row['name']).' &raquo; '.$album_name.' &raquo; '.' '.plog_tr('Slideshow');
			} else {
				$breadcrumbs = SmartStripSlashes($row['name']).' &raquo; '.$album_name;
			}

			break;
		case 'picture':
			$row = get_picture_by_id($GLOBALS['plogger_id']);
			$picture_name = get_caption_filename($row);

			$row = get_album_by_id($row['parent_album']);
			$album_name = SmartStripSlashes($row['name']);

			$row = get_collection_by_id($row['parent_id']);
			$collection_name = SmartStripSlashes($row['name']);

			$breadcrumbs = $collection_name.' &raquo; '.$album_name.' &raquo; '.$picture_name;

			// Hmm, slideshow on picture level, how does that make sense?
			if ($GLOBALS['plogger_mode'] == 'slideshow') {
				$breadcrumbs .= ' &raquo; '.plog_tr('Slideshow');
			}

			break;
		case '404':
			$breadcrumbs = ' '.plog_tr('404 Error - Not Found');
			break;
		default:
			$breadcrumbs = ' '.plog_tr('Collections');
	}

		if ($GLOBALS['plogger_level'] != '404' || $GLOBALS['plogger_level'] != 'search' || $GLOBALS['plogger_level'] != 'slideshow') {
			if (isset($_GET['plog_page'])) {
				$breadcrumbs .= ' &raquo; '.plog_tr('Page').' '.$_GET['plog_page'];
			}
		}

	return $breadcrumbs;
}

function get_caption_filename($row) {
	// Check for caption, use this instead of filename if it exists!
	if (!empty($row['caption']) > 0) {
		$picture_name = $row['caption'];
	} else {
		$picture_name = basename($row['path']);
	}
	return $picture_name;
}

function generate_jump_menu() {
	global $config;

	$output = '';
	$image_count = array();

	$output .= '<form id="jump-menu" action="#" method="get">' . "\n\t\t\t\t\t" . '<div>';
	$output .= "\n\t\t\t\t\t\t" . '<select name="jump-menu" onchange="document.location.href=this.options[this.selectedIndex].value;">
							<option value="#">'.plog_tr('Jump to').'...</option>';

	// 1. Create a list of all albums with at least one photo
	$sql = "SELECT
	`parent_album`,
	COUNT(*) AS `imagecount`
	FROM `".PLOGGER_TABLE_PREFIX."pictures`
	GROUP BY `parent_album`";
	$result = run_query($sql);

	while($row = mysql_fetch_assoc($result)) {
		$image_count[$row['parent_album']] = $row['imagecount'];
	}

	// 2. Get a list of all albums and collections
	$sqlCollection = "SELECT
	`a`.id AS `album_id`,
	`a`.name AS `album_name`,
	`c`.id AS `collection_id`,
	`c`.name AS `collection_name`
	FROM `".PLOGGER_TABLE_PREFIX."albums` AS `a`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`
	ORDER BY `c`.`name` ASC, `a`.`name` ASC";
	$result = run_query($sqlCollection);

	$last_collection = '';

	while ($row = mysql_fetch_assoc($result)) {
		// skip albums with no images
		if (empty($image_count[$row['album_id']])) {
			continue;
		}

		if ($row['collection_id'] != $last_collection) {
			$output .= "\n\t\t\t\t\t\t\t" . '<option value="'.generate_url('collection', $row['collection_id']).'">'.$row['collection_name'].'</option>';
			$last_collection = $row['collection_id'];
		}

		$output .= "\n\t\t\t\t\t\t\t" . '<option value="'.generate_url('album', $row['album_id']).'">'.SmartStripSlashes($row['collection_name']).': '.SmartStripSlashes($row['album_name']).'</option>';
	}

	$output .= "\n\t\t\t\t\t\t</select>\n\t\t\t\t\t</div>\n\t\t\t\t</form>\n";

	return $output;
}

function generate_exif_table($id) {
	global $config;
	$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE `id`=".intval($id);
	$result = run_query($query);
	if (mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		foreach($row as $key => $val) if (trim($row[$key]) == '') $row[$key] = '&nbsp;';
		// get image size
		$img = $config['basedir'].'plog-content/images/'.SmartStripSlashes($row['path']);
		list($width, $height, $type, $attr) = getimagesize($img);
		$size = round(filesize($img) / 1024, 2);
		$table_data = "\t\t\t\t\t\t" . '<div id="show_info-exif-table">
							<script type="text/javascript">flip(\'show_info-exif-table\');</script>
							<table id="exif-data">
								<tr>
									<td class="exif-label">'.plog_tr('Dimensions').':</td>
									<td class="exif-info">'.$width.' x '.$height.'</td>
								</tr>
								<tr>
									<td class="exif-label">'.plog_tr('File size').':</td>
									<td class="exif-info">'.$size.' kbytes</td>
								</tr>
								<tr>
									<td class="exif-label">'.plog_tr('Taken on').':</td>
									<td class="exif-info">'.$row['EXIF_date_taken'].'</td>
								</tr>
								<tr>
									<td class="exif-label">'.plog_tr('Camera model').':</td>
									<td class="exif-info">'.$row['EXIF_camera'].'</td>
								</tr>
								<tr>
									<td class="exif-label">'.plog_tr('Shutter speed').':</td>
									<td class="exif-info">'.$row['EXIF_shutterspeed'].'</td>
								</tr>
								<tr>
									<td class="exif-label">'.plog_tr('Focal length').':</td>
									<td class="exif-info">'.$row['EXIF_focallength'].'</td>
								</tr>
								<tr>
									<td class="exif-label">'.plog_tr('Aperture').':</td>
									<td class="exif-info">'.$row['EXIF_aperture'].'</td>
								</tr>
								<tr>
									<td class="exif-label">'.plog_tr('Flash').':</td>
									<td class="exif-info">'.$row['EXIF_flash'].'</td>
								</tr>
								<tr>
									<td class="exif-label">'.plog_tr('ISO').':</td>
									<td class="exif-info">'.$row['EXIF_iso'].'</td>
								</tr>
							</table>
						</div><!-- /show_info-exif-table -->' . "\n";
	}
	return $table_data;
}

function plogger_display_comments() {
	global $config;
	if (file_exists(THEME_DIR.'/comments.php')) {
		include(THEME_DIR.'/comments.php');
	} else {
		include($config['basedir'].'plog-content/themes/default/comments.php');
	}
}

function plogger_require_captcha() {
	$_SESSION['require_captcha'] = true;
}

// Generate header produces the gallery name, the jump menu, and the breadcrumb trail
// at the top of the image

function generate_header() {
	global $config;
	$output = '<h1 id="gallery-name">'.stripslashes($config['gallery_name']).'</h1>';
	return $output;
}

function generate_sortby($level, $id) {
	global $config;

	$output = '';

	$id = $GLOBALS['plogger_id'];

	$fields = array(
		'date' => plog_tr('Date Submitted'),
		'date_taken' => plog_tr('Date Taken'),
		'caption' => plog_tr('Caption'),
		'filename' => plog_tr('Filename'),
		'number_of_comments' => plog_tr('Number of Comments'),
	);

	if ($level == 'album') {
		// I think this should be a single form and I really should move the javascript functions
		// into a separate file
		// I need to merge those 2 forms. and since I'm reloading stuff anyway, I can just
		// create correct urls. oh yeah, baby.
		$output .= "\n\t\t\t\t\t" . '<form action="#" method="get">
						<div class="nomargin">
							<label for="change_sortby">'.plog_tr('Sort by').':</label>
							<select id="change_sortby" name="change_sortby" onchange="document.location.href=this.options[this.selectedIndex].value;">';
		foreach($fields as $fkey => $fval) {
			$value = generate_url('album', $id, array(1 => 'sorted', 'sortby' => $fkey, 'sortdir' => $_SESSION['plogger_sortdir']));
			$output .= "\n\t\t\t\t\t\t\t\t" . '<option value="'.$value.'"';
			if ($_SESSION['plogger_sortby'] == $fkey) {
				$output .= ' selected="selected"';
			}
			$output .= ">$fval</option>";
		}
		$output .= "\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</form><!-- /sort by -->";
	}
	return $output;
}

function generate_sortdir($level, $id) {
	global $config;

	$output = '';
	$id = $GLOBALS['plogger_id'];

	$orders = array(
	'asc' => plog_tr('Ascending'),
	'desc' => plog_tr('Descending'),
	);

	if ($level == 'album') {
		$output .= "\n\t\t\t\t\t" . '<form action="#" method="get">
						<div class="nomargin">
							<select id="change_sortdir" name="change_sortdir" onchange="document.location.href=this.options[this.selectedIndex].value;">';
		foreach($orders as $okey => $oval) {
			$value = generate_url('album', $id, array(1 => 'sorted', 'sortby' => $_SESSION['plogger_sortby'], 'sortdir' => $okey));
			$output .= "\n\t\t\t\t\t\t\t\t<option value=\"$value\"";
			if(strcasecmp($_SESSION['plogger_sortdir'], $okey) === 0) {
				$output .= ' selected="selected"';
			}
			$output .= ">$oval</option>";
		}
		$output .= "\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</form><!-- /sort asc/desc -->\n\t\t\t\t";
	}
	return $output;
}

function generate_search_box() {
	global $config;

	$output = '<form id="plogsearch" action="'.generate_url('search').'" method="get">
				<div id="search-box">
					<input type="text" id="searchterms" name="searchterms" />
					<input class="submit" type="submit" value="'.plog_tr('Search').'" />
				</div>';
	if (!$config['use_mod_rewrite']) {
		$output .= "\n\t\t\t\t\t" . '<input type="hidden" name="level" value="search" />';
		if (!empty($config['query_args'])) {
			$query = array();
			$query_args = explode('&amp;', $config['query_args']);
			foreach ($query_args as $value) {
				$query = explode('=', $value);
				$output .= "\n\t\t\t\t\t" . '<input type="hidden" name="'.$query[0].'" value="'.$query[1].'" />';
			}
		}
	}
	$output .= "\n\t\t\t\t" . '</form>' . "\n";

	return $output;
}

// Function for generating the slideshow javascript
function generate_slideshow_js() {
	global $config;
	$output = '';
	if($GLOBALS['plogger_mode'] == 'slideshow') {
		// output the link to the slideshow javascript
		$output = "\t" . '<script type="text/javascript" src="'.$config['gallery_url'].'plog-includes/js/plog-slideshow.js"></script>' . "\n";
	}
	return $output;
}

function preload_album_images() {
	global $thumbnail_config;
	$script = "\n\t\t" . '<script type="text/javascript">
	<!--
	function preload_images() {
		if (document.images) {
			preload_image_object = new Image();
			// set image url
			image_url = new Array();';
			$pic_array = get_picture_by_id($GLOBALS['image_list']);
			$i = 0;
			foreach($pic_array as $pic) {
				unset($path);
				$url = generate_thumb($pic['path'], $pic['id'], THUMB_LARGE);
				$script .= "\t\timage_url[$i] = '$url'\n";
				$i++;
			}
			$script .= "var i = 0;
			for(i=0; i<image_url.length; i++)
			preload_image_object.src = image_url[i];
		}
	}
	//-->
	</script>";
	return $script;
}

	// Function for generating the slideshow interface
function generate_slideshow_nav($pre_pre='', $pre='', $post='', $post_post='') {
	global $config, $thumbnail_config;
	$large_link = $pre.'<a accesskey="v" title="'.plog_tr('View Large Image').'" href="javascript:slides.hotlink()"><img src="'.THEME_URL.'images/search.gif" width="16" height="16" alt="'. plog_tr('View Large Image').'" /></a>&nbsp;&nbsp;'.$post;
	$prev_url = $pre.'<a accesskey="," title="' .plog_tr('Previous Image').'" href="javascript:slides.previous();"><img src="'.THEME_URL.'images/rewind.gif" width="16" height="16" alt="'.plog_tr('Previous Image').'" /></a>&nbsp;&nbsp;'.$post;
	$stop_url = $pre.'<a accesskey="x" title="'. plog_tr('Stop Slideshow').'" href="javascript:slides.pause();"><img src="'.THEME_URL.'images/stop.gif" width="16" height="16" alt="'.plog_tr('Stop Slideshow').'" /></a>&nbsp;&nbsp;'.$post;
	$play_url = $pre.'<a accesskey="s" title="'. plog_tr('Start Slideshow').'" href="javascript:slides.play();"><img src="'.THEME_URL.'images/play.gif" width="16" height="16" alt="'.plog_tr('Start Slideshow').'" /></a>&nbsp;&nbsp;&nbsp;'.$post;
	$next_url = $pre.'<a accesskey="." title="'.plog_tr('Next Image').'" href="javascript:slides.next();"><img src="'.THEME_URL.'images/fforward.gif" width="16" height="16" alt="'.plog_tr('Next Image').'" /></a>'.$post;
	$output = '<div class="large-thumb-toolbar" style="width: '.$thumbnail_config[THUMB_LARGE]['size'].'px;">'.$pre_pre.$large_link.$prev_url.$stop_url.$play_url.$next_url.$post_post.'</div><!-- /large-thumb-toolbar -->' . "\n";
	return $output;
}

function get_head_title() {
	global $config;
	$title = generate_title($GLOBALS['plogger_level'], $GLOBALS['plogger_id']);
	return (SmartStripSlashes($config['gallery_name']).': '.$title);
}

function plogger_head() {
	global $config;
	if ($config['embedded'] == 0) {
		// Include title and charset in <head> if gallery is not embedded in another program (i.e., WordPress)
		// Prevents duplication of title and charset if gallery is embedded
		echo "\t" . '<title>'.get_head_title().'</title>';
		echo "\n\t" . '<meta http-equiv="Content-Type" content="text/html;charset='.$config['charset'].'" />';
	}
	echo "\n\t" . '<meta http-equiv="imagetoolbar" content="false" />' . "\n";
	echo generate_slideshow_js();
	// Embed URL to RSS feed for proper level
	if (plogger_rss_link()) {
		echo "\t" . '<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="'.plogger_rss_link().'" />' . "\n";
	}
}

function is_plogger_installed() {
	global $config;
	$installed = false;

	if (defined('PLOGGER_DB_HOST')) {
		$mysql = check_mysql(PLOGGER_DB_HOST, PLOGGER_DB_USER, PLOGGER_DB_PW, PLOGGER_DB_NAME);
		if (empty($mysql)) {
			$sql = "DESCRIBE `".PLOGGER_TABLE_PREFIX."config`";
			$result = mysql_query($sql);
			if ($result) {
				$installed = true;
				$config_sql = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."config`";
				$config_result = mysql_query($config_sql);
				$config = mysql_fetch_assoc($config_result);
			}
		}
	}
	return $installed;
}

function check_mysql($host, $user, $pass, $database) {
	$errors = array();
	if (function_exists('mysql_connect')) {
		$connection = @mysql_connect($host, $user, $pass);
		if (!$connection) {
			$errors[] = plog_tr('Cannot connect to MySQL with the information provided. MySQL error: ').mysql_error();
		}
	}
	$select = @mysql_select_db($database);
	if (!$select) {
		$errors[] = sprintf(plog_tr('Cannot find the database %s. MySQL error: '), '<strong>'.$database.'</strong>').mysql_error();
	}
	return $errors;
}

function connect_db() {
	global $config, $PLOGGER_DBH;

	if (!isset($PLOGGER_DBH)) {
		$PLOGGER_DBH = mysql_connect(PLOGGER_DB_HOST, PLOGGER_DB_USER, PLOGGER_DB_PW) or die(plog_tr('Plogger cannot connect to the database because: ').mysql_error());

		mysql_select_db(PLOGGER_DB_NAME);

		$mysql_version = mysql_get_server_info();
		$mysql_charset_support = '4.1';

		if (1 == version_compare($mysql_version, $mysql_charset_support)) {
			mysql_query('SET NAMES utf8');
		}
	}

}

function close_db() {
	global $PLOGGER_DBH;

	if (isset($PLOGGER_DBH)) {
		mysql_close($PLOGGER_DBH);
	}
}

function run_query($query) {
	global $PLOGGER_DBH;

	if (defined('PLOGGER_DEBUG') && PLOGGER_DEBUG == '1') {
		$GLOBALS['query_count']++;
		$GLOBALS['queries'][] = $query;
	}

	$result = @mysql_query($query, $PLOGGER_DBH);

	if (!$result) {
		$trace = debug_backtrace();

		die(mysql_error($PLOGGER_DBH).'<br /><br />' .
		$query.'<br /><br />
		In file: '.$_SERVER['PHP_SELF'].'<br /><br />
		On line: '.$trace[0]['line']);
	} else {
		return $result;
	}
}

function get_active_collections_albums() {
	$image_collection_count = array();
	$image_album_count = array();
	$return = array(
		'collections' => '',
		'albums' => '');

	$sql = "SELECT parent_collection, parent_album FROM `".PLOGGER_TABLE_PREFIX."pictures` GROUP BY parent_collection, parent_album";
	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$image_collection_count[$row['parent_collection']] = 1;
		$image_album_count[$row['parent_album']] = 1;
	}
	$return['collections'] = array_keys($image_collection_count);
	$return['albums'] = array_keys($image_album_count);
	return $return;
}

function generate_thumb($path, $prefix, $type = THUMB_SMALL) {
	global $config, $thumbnail_config;

	$thumb_config = $thumbnail_config[$type];

	// For relative paths assume that they are relative to 'plog-content/images/' directory,
	// otherwise just use the given path
	if (file_exists($path)) {
		$source_file_name = $path;
		if ($type == THUMB_THEME) {
			$cache_path = 'themes/';
		} else {
			$cache_path = 'uploads/';
		}
	} else {
		$source_file_name = $config['basedir'].'plog-content/images/'.SmartStripSlashes($path);
		$cache_path = dirname(SmartStripSlashes($path)).'/'.$thumb_config['type'].'/';
	}

	// The file might have been deleted and since phpThumb dies in that case
	// try to do something sensible so that the rest of the images can still be seen

	// There is a problem in safe mode - if the script and picture file are owned by
	// different users, then the file cannot be read.

	if (!is_readable($source_file_name)) {
		return false;
	}

	$imgdata = @getimagesize($source_file_name);

	if (!$imgdata) {
		// Unknown image format, bail out
		// Do we want to have video support in the Plogger core?
		//return 'plog-graphics/thumb-video.gif';
		return false;
	}

	// Attributes of original image
	$orig_width = $imgdata[0];
	$orig_height = $imgdata[1];

	// XXX: food for thought - maybe we can return URL to some kind of error image
	// if this function fails?

	$base_filename = sanitize_filename(basename($path));

	if ($thumb_config['disabled']) {
		return $config['gallery_url'].'plog-content/images/'.$path;
	}

	$prefix = $prefix.'-';

	$thumbpath = $config['basedir'].'plog-content/thumbs/'.$cache_path.$prefix.$base_filename;
	$thumburl = $config['gallery_url'].'plog-content/thumbs/'.$cache_path.$prefix.$base_filename;

	// If thumbnail file already exists and is generated after data for a thumbnail type
	// has been changed, then we assume that the thumbnail is valid.
	if (file_exists($thumbpath)) {
		$thumbnail_timestamp = @filemtime($thumbpath);
		if ($thumb_config['timestamp'] < $thumbnail_timestamp) {
			return $thumburl;
		}
	}

	// Create the same directory structure as the image under plog-content/thumbs/
	include_once(PLOGGER_DIR.'plog-admin/plog-admin-functions.php');
	if (!makeDirs(dirname($thumbpath))) {
		return sprintf(plog_tr('Error creating path %s'), dirname($thumbpath));
	}

	// If dimensions of source image are smaller than those of the requested
	// thumbnail, then use the original image as thumbnail unless fullsize images are disabled
	if ($orig_width <= $thumb_config['size'] && $orig_height <= $thumb_config['size']) {
		// if fullsize image access is disabled, copy the file to the thumbs folder
		if ($config['allow_fullpic'] == 0) {
			copy($source_file_name, $thumbpath); 
			return $thumburl;
		// otherwise return the original file path
		} else {
			return $config['gallery_url'].'plog-content/images/'.$path;
		}
	}

	// No existing thumbnail found or thumbnail config has changed,
	// generate new thumbnail file
	require_once(PLOGGER_DIR.'plog-includes/lib/phpthumb/phpthumb.class.php');
	$phpThumb = new phpThumb();

	// Set data
	$phpThumb->setSourceFileName($source_file_name);
	switch ($thumb_config['resize_option']) {
	// Resize to width
	case 0:
		$phpThumb->w = $thumb_config['size'];
		break;
	// Resize to height
	case 1:
		$phpThumb->h = $thumb_config['size'];
		break;
	// Use square thumbnails
	case 3:
		$phpThumb->zc = 1;
		$phpThumb->h = $thumb_config['size'];
		$phpThumb->w = $thumb_config['size'];
		break;
	// Resize to longest side
	case 2:
	default:
		if ($imgdata[0] > $imgdata[1]) {
			$phpThumb->w = $thumb_config['size'];
		} else {
			$phpThumb->h = $thumb_config['size'];
		}
	}

	$phpThumb->q = $config['compression'];

	if($type == THUMB_NAV) {
		$phpThumb->zc = 1;
		$phpThumb->h = $thumb_config['size'];
		$phpThumb->w = $thumb_config['size'];
	}

	if($type == THUMB_THEME) {
		$phpThumb->w = $thumb_config['size'];
	}

	// Set options (see phpThumb.config.php)
	// here you must preface each option with "config_"

	// Disable ImageMagick - set to false for localhost testing
	// ImageMagick seems to cause some issues on localhost using FF or Chrome
	$phpThumb->config_prefer_imagemagick = false;

	// We want to use the original image for thumbnail creation, not the EXIF stored thumbnail
	$phpThumb->config_use_exif_thumbnail_for_speed = false;

	// Set error handling (optional)
	$phpThumb->config_error_die_on_error = false;

	// If safe_mode enabled, open the permissions first
	if (is_safe_mode()) {
		$thumb_path = dirname($thumbpath).'/';
		chmod_ftp($thumb_path, 0777);
	}

	// Generate & output thumbnail
	if ($phpThumb->GenerateThumbnail()) {
		$phpThumb->RenderToFile($thumbpath);
	} else {
		// do something with debug/error messages
		die('Failed: '.implode("\n", $phpThumb->debugmessages));
	}
	@chmod($thumbpath, PLOGGER_CHMOD_FILE);

	// If safe_mode enabled, close the permissions back down to the default
	if (is_safe_mode()) {
		chmod_ftp($thumb_path);
	}

	return $thumburl;
}

function check_picture_id($id) {
	$sql = "SELECT `parent_album` FROM ".PLOGGER_TABLE_PREFIX."pictures WHERE `id` = ".intval($id);
	$result = run_query($sql);
	if (mysql_num_rows($result) > 0) {
		return true;
	} else {
		$GLOBALS['plogger_level'] = '404';
		return false;
	}
}

function get_picture_by_id($id, $album_id = null) {
	global $config;

	if(is_array($id)) {
		foreach ($id as $key => $val) {
			$id[$key] = intval($val);
		}
		$where_cond = "IN ('".implode("', '", $id)."')";
	} else {
		$where_cond = "= ".intval($id);
	}

	$sql = "SELECT
	`p`.*,
	`a`.`path` AS `album_path`,
	`c`.`path` AS `collection_path`
	FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `p`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."albums` AS `a` ON `p`.`parent_album`=`a`.`id`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `p`.`parent_collection`=`c`.`id`
	WHERE `p`.`id` ".$where_cond;

	if ($album_id) {
		$sql .= " AND `p`.`parent_album`=".intval($album_id);
	}

	$resultPicture = run_query($sql);

	if (is_array($id) && mysql_num_rows($resultPicture) > 0) {
		$picdata = array();
		while ($row = mysql_fetch_assoc($resultPicture)) {
			$row['url'] = $config['gallery_url'].'plog-content/images/'.$row['collection_path'].'/'.$row['album_path'].'/'.basename($row['path']);
			array_unshift($picdata, $row);
		}
	} elseif (!is_array($id) && mysql_num_rows($resultPicture) > 0) {
		$picdata = mysql_fetch_assoc($resultPicture);

		// Eventually I want to get rid of the full path in pictures tables to avoid useless data duplication
		// The following is a temporary solution so I don't have to break all the functionality at once
		$picdata['url'] = $config['gallery_url'].'plog-content/images/'.$picdata['collection_path'].'/'.$picdata['album_path'].'/'.basename($picdata['path']);
	} else {
		$picdata = false;
	}

	return $picdata;
}

function get_pictures($album_id, $order = 'alpha', $sort = 'DESC') {
	global $config;

	$query = "SELECT
	`p`.*,
	`a`.`path` AS `album_path`,
	`c`.`path` AS `collection_path`
	FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `p`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."albums` AS `a` ON `p`.`parent_album`=`a`.`id`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `p`.`parent_collection`=`c`.`id`
	WHERE `a`.`id`=".intval($album_id);

	if ($order == 'mod') {
		$query .= ' ORDER BY `p`.`date_submitted` ';
	} else {
		$query .= ' ORDER BY `p`.`caption` ';
	}

	if ($sort == 'ASC') {
		$query .= ' ASC ';
	} else {
		$query .= ' DESC ';
	}

	$result = run_query($query);

	$pictures = array();

	while ($row = mysql_fetch_assoc($result)) {
		// See comment in get_picture_by_id
		$row['url'] = $config['gallery_url'].'plog-content/images/'.$row['collection_path'].'/'.$row['album_path'].'/'.basename($row['path']);
		$pictures[$row['id']] = $row;
	}

	return $pictures;
}

function check_album_id($id) {
	$sql = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."albums WHERE `id` = ".intval($id);
	$result = run_query($sql);
	if (mysql_num_rows($result) > 0) {
		$GLOBALS['current_album'] = mysql_fetch_assoc($result);
		return true;
	} else {
		$GLOBALS['plogger_level'] = '404';
		return false;
	}
}

function get_album_by_id($id) {
	global $config;

	$sql = "SELECT
	`a`.*,
	`c`.`path` AS `collection_path`,
	`a`.`path` AS `album_path`,
	`c`.`name` AS `collection_name`,
	`a`.`name` AS `album_name`
	FROM `".PLOGGER_TABLE_PREFIX."albums` AS `a`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."pictures` AS `i` ON `a`.`thumbnail_id`=`i`.`id`
	WHERE `a`.`id` = ".intval($id);
	$result = run_query($sql);

	if (mysql_num_rows($result) > 0) {
		$album = mysql_fetch_assoc($result);

		if ($album['thumbnail_id'] == 0) {
			$query = "SELECT `id`, `path`
			FROM `".PLOGGER_TABLE_PREFIX."pictures`
			WHERE `parent_album`=".intval($album['id'])."
			ORDER BY `date_submitted` DESC
			LIMIT 1";
			$result = run_query($query);

			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_assoc($result);
				$album['thumbnail_id'] = $row['id'];
			}
		}
	} else {
		$album = false;
	}

	return $album;
}

function get_album_by_name($name, $collection_id) {
	$sql = "SELECT *
	FROM `".PLOGGER_TABLE_PREFIX."albums`
	WHERE `name` = '".mysql_real_escape_string($name)."'
	AND `parent_id` = ".intval($collection_id);
	$result = run_query($sql);
	if (mysql_num_rows($result) > 0) {
		$album = mysql_fetch_assoc($result);
	} else {
		$album = false;
	}
	return $album;
}

function check_collection_id($id) {
	$sql = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."collections WHERE `id` = ".intval($id);
	$result = run_query($sql);
	if (mysql_num_rows($result) > 0) {
		$GLOBALS['current_collection'] = mysql_fetch_assoc($result);
		return true;
	} else {
		$GLOBALS['plogger_level'] = '404';
		return false;
	}
}

function get_collection_by_id($id) {
	global $config;

	$sqlCollection = "SELECT `c`.*,
	`c`.`path` AS `collection_path`
	FROM `".PLOGGER_TABLE_PREFIX."collections` AS `c`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."pictures` AS `i` ON `c`.`thumbnail_id`=`i`.`id`
	WHERE `c`.`id`=".intval($id)."
	ORDER BY `c`.`name` ASC";
	$resultCollection = run_query($sqlCollection);

	if (mysql_num_rows($resultCollection) == 0) {
		$collection = false;
	} else {
		$collection = mysql_fetch_assoc($resultCollection);

		if ($collection['thumbnail_id'] == 0) {
			$query = "SELECT `id`, `path`
			FROM `".PLOGGER_TABLE_PREFIX."pictures`
			WHERE `parent_collection`=".intval($collection['id'])."
			ORDER BY `date_submitted` DESC
			LIMIT 1";
			$result = run_query($query);

			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_assoc($result);
				$collection['thumbnail_id'] = $row['id'];
			}
		}
	}

	return $collection;
}

function get_collection_by_name($name) {
	$sql = "SELECT *
	FROM `".PLOGGER_TABLE_PREFIX."collections`
	WHERE name = '".mysql_real_escape_string($name)."'";
	$result = run_query($sql);
	if (mysql_num_rows($result) > 0) {
		$collection = mysql_fetch_assoc($result);
	} else {
		$collection = false;
	}
	return $collection;
}

function get_albums($collection_id = null, $sort = 'alpha', $order = 'DESC') {
	global $config;

	$albums = array();

	if ($sort == 'mod') {
		$query = "SELECT
		`a`.`id` AS `album_id`,
		`a`.`name` AS `album_name`,
		`c`.`id` AS `collection_id`,
		`c`.`name` AS `collection_name`,
		`a`.`description`,
		`a`.`thumbnail_id`
		FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `i`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."albums` AS `a` ON `i`.`parent_album`=`a`.`id`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `i`.`parent_collection`=`c`.`id`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."pictures` AS `i2` ON `a`.`thumbnail_id`=`i2`.`id`";

		if ($collection_id) {
			$query .= " WHERE `i`.`parent_collection`=".intval($collection_id);
		}

		$query .= "
		GROUP BY `i`.`parent_album`
		ORDER BY `i`.`date_submitted` ";

		if ($order == 'ASC') {
			$query .= ' ASC ';
		} else {
			$query .= ' DESC ';
		}
	} else {
		$query = "SELECT
		`a`.`id` AS `album_id`,
		`a`.`name` AS `album_name`,
		`c`.`id` AS `collection_id`,
		`c`.`name` AS `collection_name`,
		`a`.`description`,
		`a`.`thumbnail_id`
		FROM `".PLOGGER_TABLE_PREFIX."albums` AS `a`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."pictures` AS `i` ON `a`.`thumbnail_id`=`i`.`id`";

		if ($collection_id) {
			$query .= " WHERE `c`.id=".intval($collection_id)." ";
		}

		$query .= " ORDER BY `c`.`name` ASC, `a`.`name` ASC";
	}

	$result = run_query($query);

	while ($album = mysql_fetch_assoc($result)) {
		if ($album['thumbnail_id'] == 0) {
			$query = "SELECT `id`, `path`
			FROM `".PLOGGER_TABLE_PREFIX."pictures`
			WHERE `parent_album`=".intval($album['album_id'])."
			ORDER BY `date_submitted` DESC
			LIMIT 1";
			$thumb_result = run_query($query);

			if (mysql_num_rows($thumb_result) > 0) {
				$row = mysql_fetch_assoc($thumb_result);
				$album['thumbnail_id'] = $row['id'];
			}
		}

		$albums[$album['album_id']] = $album;
	}

	return $albums;
}

function get_collections($sort = 'alpha', $order = 'DESC') {
	global $config;

	if ($sort == 'mod') {
		$query = "SELECT `c`.*
		FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `i`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `i`.`parent_collection`=`c`.`id`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."pictures` AS `i2` ON `c`.`thumbnail_id`=`i2`.`id`
		GROUP BY `i`.`parent_collection`
		ORDER BY `i`.`date_submitted` ";

		if ($order == 'ASC') {
			$query .= ' ASC ';
		} else {
			$query .= ' DESC ';
		}
	} else {
		$query = "SELECT `c`.*
		FROM `".PLOGGER_TABLE_PREFIX."collections` AS `c`
		ORDER BY `c`.`name` ";

		if ($order == 'ASC') {
			$query .= ' ASC ';
		} else {
			$query .= ' DESC ';
		}
	}

	$resultCollection = run_query($query);

	$collections = array();

	while ($collection = mysql_fetch_assoc($resultCollection)) {
		if ($collection['thumbnail_id'] == 0) {
			$query = "SELECT `id`, `path`
			FROM `".PLOGGER_TABLE_PREFIX."pictures`
			WHERE `parent_collection`=".intval($collection['id'])."
			ORDER BY `date_submitted` DESC
			LIMIT 1";
			$result = run_query($query);

			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_assoc($result);
				$collection['thumbnail_id'] = $row['id'];
			}
		}

		$collections[$collection['id']] = $collection;
	}

	return $collections;
}

function SmartAddSlashes($str) {
	if (get_magic_quotes_gpc()) {
		return $str;
	} else {
		return addslashes($str);
	}
}

function SmartStripSlashes($str) {
	if (get_magic_quotes_gpc()) {
		return stripslashes($str);
	} else {
		return $str;
	}
}

// This tries hard to figure out level and object id from textual path to a resource, used
// mostly if mod_rewrite is in use
function resolve_path($str = '') {
	$rv = array();
	$path_parts = explode('/', $str);

	$levels = array('collection', 'album', 'picture', 'arg1', 'arg2');

	$current_level = '';

	$names = array();

	foreach($levels as $key => $level) {
		if (isset($path_parts[$key])) {
			$names[$level] = mysql_real_escape_string(urldecode(SmartStripSlashes($path_parts[$key])));
			$current_level = $level;
		}
	}

	if (!empty($names['collection'])) {
		// Check for collections level pagination first
		if ($names['collection'] == 'page' && !empty($names['album']) && is_numeric($names['album']) && intval($names['album']) == $names['album']) {
			return array('level' => 'collections', 'id' => 0, 'plog_page' => intval($names['album']));
		}
		if ($names['collection'] == 'search') {
			$return = array('level' => 'search');
			if (isset($names['album']) && !empty($names['album'])) {
				$return['searchterms'] = str_replace('?searchterms=', '', $names['album']);
			}
			if (isset($names['picture']) && $names['picture'] == 'page' && is_numeric($names['arg1'])) {
				$return['plog_page'] = intval($names['arg1']);
			}
			if (isset($names['picture']) && $names['picture'] == 'slideshow') {
				$return['mode'] = 'slideshow';
			}
			return $return;
		}
		$sql = "SELECT *
		FROM `".PLOGGER_TABLE_PREFIX."collections`
		WHERE `path`='".$names['collection']."'";
		$result = run_query($sql);

		// No such collection
		if (mysql_num_rows($result) == 0) {
			// Check if it's an RSS feed
			if ($names['collection'] == 'feed') {
				return array('level' => 'collections', 'id' => 0);
			// Else throw a 404 error
			} else {
				return array('level' => '404');
			}
		}

		$collection = mysql_fetch_assoc($result);

		// What if there are multiple collections with same names? I hope there aren't .. this would
		// suck. But here is an idea, we shouldn't allow the user to enter similar names
		$rv = array('level' => 'collection', 'id' => $collection['id']);
	}

	if (!empty($names['album'])) {
		// Check for collection level pagination first
		if ($names['album'] == 'page' && !empty($names['picture']) && is_numeric($names['picture']) && intval($names['picture']) == $names['picture']) {
			return array('level' => 'collection', 'id' => $collection['id'], 'plog_page' => intval($names['picture']));
		}
		$sql = "SELECT *
		FROM `".PLOGGER_TABLE_PREFIX."albums`
		WHERE `path`='".$names['album']."'
		AND `parent_id`=".intval($collection['id']);
		$result = run_query($sql);

		// No such album
		if (mysql_num_rows($result) == 0) {
			// Check if it's an RSS feed
			if ($names['album'] == 'feed') {
				return array('level' => 'collection', 'id' => $collection['id']);
			// Else throw a 404 error
			} else {
				return array('level' => '404');
			}
		}

		$album = mysql_fetch_assoc($result);

		// Try to detect slideshow. Downside is that you cannot have a picture with that name
		if (isset($names['picture']) && $names['picture'] == 'slideshow') {
			return array('level' => 'album', 'mode' => 'slideshow', 'id' => $album['id']);
		}

		// Deal with http://plogger/collection/album/sorted/field/asc and friends
		if (isset($names['picture']) && $names['picture'] == 'sorted') {
			if (isset($names['arg1'])) {
				$_SESSION['plogger_sortby'] = $names['arg1'];
			}

			if (isset($names['arg2'])) {
				$_SESSION['plogger_sortdir'] = $names['arg2'];
			}

			return array('level' => 'album', 'id' => $album['id']);
		}

		$rv = array('level' => 'album', 'id' => $album['id']);
	}

	if (!empty($names['picture'])) {
		// Check for album level pagination first
		if ($names['picture'] == 'page' && !empty($names['arg1']) && is_numeric($names['arg1']) && intval($names['arg1']) == $names['arg1']) {
			return array('level' => 'album', 'id' => $album['id'], 'plog_page' => intval($names['arg1']));
		}
		$sql = "SELECT *
		FROM `".PLOGGER_TABLE_PREFIX."pictures`
		WHERE `caption`='".$names['picture']."'
		AND `parent_album`=".intval($album['id']);
		$result = run_query($sql);

		$picture = mysql_fetch_assoc($result);

		// No such caption, perhaps we have better luck with path?
		if (!$picture) {
			// Check if it's an RSS feed for the picture comments
			while (!empty($names['arg1']) && $names['arg1'] == 'feed') {
				$feed = array_pop($names);
			}
			$filepath = join('/', $names);
			$like_match = array('_', '%');
			$like_replace = array('\_', '\%');
			$filepath = str_replace($like_match, $like_replace, $filepath);
			$sql = "SELECT *
			FROM `".PLOGGER_TABLE_PREFIX."pictures`
			WHERE `path` LIKE '".$filepath.".%'
			AND `parent_album`=".intval($album['id']);
			$result = run_query($sql);
			$picture = mysql_fetch_assoc($result);
		}

		// No such picture
		if (!$picture) {
			// Check if it's an RSS feed
			if ($names['picture'] == 'feed') {
				return array('level' => 'album', 'id' => $album['id']);
			// Else throw a 404 error
			} else {
				return array('level' => '404');
			}
		}

		$rv = array('level' => 'picture', 'id' => $picture['id']);
	}

	return $rv;
}

function generate_pagination($level, $id, $current_page, $items_total, $items_on_page, $args = array(), $page_range = false) {
	$output = '';

	if (!isset($GLOBALS['total_pictures'])) $GLOBALS['total_pictures'] = 0;

	if (($items_total == 0) && ($GLOBALS['total_pictures'] > 0)) {
		$items_total = $GLOBALS['total_pictures'];
	}

	$num_pages = ceil($items_total / $items_on_page);

	if ($num_pages > 1) {
		if ($current_page > 1) {
			if ($current_page != 2) {
				$args[1] = 'page';
				$args['plog_page'] = $current_page - 1;
			}
			$output .= '<a title="'.plog_tr('Previous').'" accesskey="," class="pagPrev" href="'.generate_url($level, $id, $args).'"><span>&laquo;</span></a>';
		}

		if ($page_range !== false && $num_pages > $page_range) {
			// Set 5 as minimum page range so we always have 1 page to either side of currrent
			if ($page_range < 5) {
				$range = 5;
			} else {
				$range = $page_range;
			}
		} else {
			$range = $num_pages;
			$beginning = $end = true;
		}
		$page_list_array = array('<span class="page-link-current">'.$current_page.'</span>');
		$count = 1;
		for($i=1; $i<$range; $i++) {
			// If we're at or over our range, break the loop
			if ($count >= $range) {
				break;
			}
			$prev = $current_page-$i;
			$next = $current_page+$i;
			// Use unshift() to add to the beginning, push() to add to the end
			if($prev > 0) {
				// Add a previous page
				if ($prev != 1) {
					$args[1] = 'page';
					$args['plog_page'] = $prev;
				} else {
					unset($args[1]);
					unset($args['plog_page']);
				}
				$prev_page = '<a href="'.generate_url($level, $id, $args).'" class="page-link">'.$prev.'</a>';
				array_unshift($page_list_array, $prev_page);
				if ($prev == 1) {
					$start = true;
				}
				$count++;
			} else {
				$start = true;
			}
			if($next <= $num_pages) {
				// Add a next page
				$args[1] = 'page';
				$args['plog_page'] = $next;
				$next_page = '<a href="'.generate_url($level, $id, $args).'" class="page-link">'.$next.'</a>';
				array_push($page_list_array, $next_page);
				if ($next >= $num_pages) {
					$end = true;
				}
				$count++;
			} else {
				$end = true;
			}
		}
		// Check our range again and use array_shift to remove a single previous page
		if (count($page_list_array) > $range) {
			if ($current_page - ceil($range/2) <= 1) {
				array_pop($page_list_array);
			} else {
				array_shift($page_list_array);
			}
		}
		// If the beginning was not reached, add the first page link with ellipses
		if (!isset($start)) {
			$first = '<a href="'.generate_url($level, $id, array(1 => 'page', 'plog_page' => 1)).'" class="page-link">1</a>...';
			// If the end was reached, shift one off the beginning
			if (isset($end)) {
				array_shift($page_list_array);
			// Otherwise pop one off the end
			} else {
				array_pop($page_list_array);
			}
		} else {
			$first = '';
		}
		// If the end was not reached, add the last page link with ellipses
		if (!isset($end)) {
			$last = '...<a href="'.generate_url($level, $id, array(1 => 'page', 'plog_page' => $num_pages)).'" class="page-link">'.$num_pages.'</a>';
			// If the beginning was reached, pop one off the end
			if (isset($start)) {
				array_pop($page_list_array);
			// Otherwise shift one off the beginning
			} else {
				array_shift($page_list_array);
			}
		} else {
			$last = '';
		}

		$output .= $first;
		//$output .= implode('', $page_list_array);
		foreach ($page_list_array as $page) {
			$output .= $page;
		}
		$output .= $last;

		if ($current_page != $num_pages) {
			$args[1] = 'page';
			$args['plog_page'] = $current_page + 1;
			$output .= '<a title="'.plog_tr('Next').'" accesskey="." class="pagNext" href="'.generate_url($level, $id, $args).'"><span>&raquo;</span></a>';
		}
	}

	return $output;
}

// Sanitize filename by replacing international characters with underscores
function sanitize_filename($str, $is_file = false) {
	global $config;
	// Allow only alphanumeric characters, hyphen, and dot in file names
	// Spaces will be changed to dashes, special chars will be suppressed, & the rest will be replaced with underscores
	$special_chars = array ('#', '$', '%', '^', '&', '*', '!', '~', '"', '\'', '=', '?', '/', '[', ']', '(', ')', '|', '<', '>', ';', ':', '\\', ', ');
	$str = str_replace($special_chars, '', $str);
	$str = str_replace(' ', '-', $str);
	$str = preg_replace("/[^a-zA-Z0-9\-\.]/", "_", $str);
	if ($is_file && intval($config['truncate']) > 0 && isset($str{intval($config['truncate'])})) {
		$str = substr($str, 0, intval($config['truncate']));
	}
	return $str;
}

function generate_url($level, $id = -1, $arg = array(), $plaintext = false) {
	global $config;

	$rv = '';

	if ($config['use_mod_rewrite'] && $level != 'admin') {
		$args = '';
		// I need to give additional arguments to the URLs
		if (sizeof($arg) > 0) {
			foreach($arg as $aval) {
				$args .= $aval.'/';
			}
		}

		switch($level) {
			case 'collection':
				$query = "SELECT `path` FROM `".PLOGGER_TABLE_PREFIX."collections` WHERE `id`=".intval($id);
				$result = run_query($query);
				$row = mysql_fetch_assoc($result);
				$rv = $config['baseurl'].rawurlencode(SmartStripSlashes($row['path'])).'/'.$args;
				break;
			case 'album':
				$query = "SELECT
				`c`.`path` AS `collection_path`,
				`a`.`path` AS `album_path`
				FROM `".PLOGGER_TABLE_PREFIX."albums` AS `a`
				LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`
				WHERE `a`.`id`=".intval($id);
				$result = run_query($query);
				$row = mysql_fetch_assoc($result);
				$rv = $config['baseurl'].rawurlencode(SmartStripSlashes($row['collection_path'])).'/'.rawurlencode(SmartStripSlashes($row['album_path'])).'/'.$args;
				break;
			case 'picture':
				$pic = get_picture_by_id($id);
				//$album = $pic['parent_album'];
				$rv = $config['baseurl'].str_replace('%2F', '/', rawurlencode(substr(SmartStripSlashes($pic['path']), 0, strrpos($pic['path'], '.')))).'/';
				break;
			case 'search':
				$rv = $config['baseurl'].'search/'.$args;
				break;
			case 'collections':
			default:
				$rv = $config['baseurl'].$args;
				break;
		}
	} else {
		// If there are non-Plogger query items, get them here to prepend to the URL query string
		// Non-Plogger query items only work with old style URLs (not with mod_rewrite URLs)
		$query = (isset($config['query_args'])) ? '?'.$config['query_args'].'&amp;' : '?';

		$args = '';
		// Add on any additional arguments from the $arg array
		if (sizeof($arg) > 0) {
			foreach($arg as $akey => $aval) {
				// mod_rewrite URLs need /sorted and /plog_page in them, the old style ones do not.
				// This temporary workaround removes the 'sorted' and 'plog_page' strings
				if (!is_numeric($akey)) {
					$args .= '&amp;'.$akey.'='.$aval;
				}
			}
		}

		switch($level) {
			// Admin section for generate_url
			case 'admin':
				$rv = $config['baseurl'].'plog-admin/plog-'.$id.'.php?'.substr($args, 5);
				break;
			// Front end section for generate_url
			case 'collection':
				$rv = $config['baseurl'].$query.'level=collection&amp;id='.$id.$args;
				break;
			case 'album':
				$rv = $config['baseurl'].$query.'level=album&amp;id='.$id.$args;
				break;
			case 'picture':
				$rv = $config['baseurl'].$query.'level=picture&amp;id='.$id;
				break;
			case 'search':
				$rv = $config['baseurl'].$query.'level=search'.$args;
				break;
			case 'collections':
			default:
				$rv = $config['baseurl'];
				if ($query != '?' || !empty($args)) {
					// remove &amp; from the end of $query since we do not have level or id at collections level
					$query = str_replace('&amp;', '', $query);
					// remove &amp; from the beginning of $args if no previous query set
					if ($query == '?') {
						$args = substr($args, 5);
					}
					// append the $query or $args
					$rv .= $query.$args;
				}
				break;
		}
	}

	// Replace &amp; with & if formatting plaintext (i.e. outputting to email)
	if ($plaintext !== false) {
		$rv = str_replace('&amp;', '&', $rv);
	}

	return $rv;
}

function add_comment($parent_id, $author, $email, $url, $comment) {
	global $config;

	if (empty($config['allow_comments'])) {
		return array('errors' => plog_tr('Comments disabled'));
	}

	$ip = $_SERVER['REMOTE_ADDR'];
	$host = gethostbyaddr($ip);

	// I want to use the original unescaped values later - to send the email
	$sql_author = mysql_real_escape_string($author);
	$sql_email = mysql_real_escape_string($email);
	$sql_url = mysql_real_escape_string($url);
	$sql_comment = mysql_real_escape_string($comment);
	$sql_ip = mysql_real_escape_string($ip);

	$parent_id = intval($parent_id);

	$result = array();

	$picdata = get_picture_by_id($parent_id);

	if (empty($picdata)) {
		return array('errors' => plog_tr('Could not post comment - no such picture'));
	}

	if (empty($picdata['allow_comments'])) {
		return array('errors' => plog_tr('Comments disabled'));
	}

	if ($config['comments_moderate'] == 1) {
		$approved = 0;
		$notify_msg = " ".plog_tr('(awaiting your approval)');
	} else {
		$approved = 1;
		$notify_msg = '';
	}

	// Right now all comments will be approved, spam protection can be implemented later
	$query = "INSERT INTO `".PLOGGER_TABLE_PREFIX."comments` SET
	`author`= '$sql_author',
	`email`= '$sql_email',
	`url`= '$sql_url',
	`date`= NOW(),
	`comment`= '$sql_comment',
	`parent_id`= '$parent_id',
	`approved` = '$approved',
	`ip` = '$ip'";
	$result = mysql_query($query);

	if (!$result) {
		return array('errors' => plog_tr('Could not post comment').mysql_error());
	}

	// XXX: admin email address should be validated
	if ($config['comments_notify'] && $config['admin_email']) {
		// Create and send notify mail message
		$msg = plog_tr('New comment posted for picture').": ".basename($picdata['path'])." $notify_msg\n\n";
		$msg .= plog_tr('Author').": $author (IP: $ip, Remote Host: $host)\n";
		$msg .= plog_tr('Email').": $email\n";
		$msg .= plog_tr('URL').": $url\n\n";
		$msg .= plog_tr('Comment').":\n$comment\n\n";
		$msg .= plog_tr('You can see all the comments for this picture here').":\n";
		$picurl = generate_url("picture", $parent_id, NULL, true);
		$msg .= $picurl;
		$headers = "From: $author <$email>\r\n";
		$headers .= "Content-type: text/plain; charset=utf-8\r\n";

		// Subject should be encoded for international characters too!
		@mail(
		$config['admin_email'],
		'['.SmartStripSlashes($config['gallery_name']).'] '.plog_tr('New Comment From').' '.$author,
		SmartStripSlashes($msg),
		$headers);
	}

	return array('result' => plog_tr('Comment added').'.');
}

// Begin basic Plogger API functions

// plogger_list_categories()
// This function will create a list of nested categorical links for use in sidebars

function plogger_list_categories($class) {
	// first select id and name for all collections
	$query = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."collections";
	$result = run_query($query);

	$output = "\n" . '<ul class="'.$class.'">';

	// Loop through each collection, output child albums
	while ($row = mysql_fetch_assoc($result)) {
		// Output collection name
		$collection_link = '<a href="'.generate_url('collection', $row['id']).'">'.$row['name'].'</a>';
		$output .= "\n\t" . '<li>'.$collection_link.'</li>';

		// Loop through child albums
		$query = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."albums WHERE parent_id = '$row[id]' ORDER BY name DESC";

		$output .= "\n<ul>";
		while ($albums = mysql_fetch_assoc($result)) {
			$album_link = '<a href="'.generate_url('albums', $albums['id']).'">'.$albums['name'].'</a>';
			$output .= "\n\t" . '<li>'.$album_link.'</li>';
		}

		$output .= "\n</ul>";
	}

	$output .= "\n</ul>";

	echo $output;
}

function is_allowed_extension($ext) {
	// return in_array(strtolower($ext), array('jpg', 'gif', 'png', 'bmp', 'mp4', 'wmv'));
	return in_array(strtolower($ext), array('jpg', 'jpeg', 'gif', 'png', 'bmp'));
}

function is_safe_mode() {
	if (ini_get('safe_mode') && function_exists('ftp_connect')) {
		return true;
	}
	return false;
}

// Benchmark timing
function plog_timer($time = 'start') {
	if (defined('PLOGGER_DEBUG') && PLOGGER_DEBUG == '1') {
		global $plog_start_time;
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$mtime = $mtime[1] + $mtime[0];
		if ($time == 'end') {
			return 'Page created in '.($mtime-$plog_start_time).' seconds';
		} else {
			return $mtime;
		}
	}
}

function plogger_init() {
	global $config;

	$page = isset($_GET['plog_page']) ? intval($_GET['plog_page']) : 1;
	$from = ($page - 1) * $config['thumb_num'];

	if ($from < 0) {
		$from = 0;
	}

	// We shouldn't set a limit for the slideshow
	if ($GLOBALS['plogger_mode'] == 'slideshow') {
		$lim = -1;
	} else {
		$lim = $config['thumb_num'];
	}

	$id = $GLOBALS['plogger_id'];

	if ($GLOBALS['plogger_level'] == 'search') {
		plogger_init_search(array(
		'searchterms' => $_REQUEST['searchterms'],
		'from' => $from,
		'limit' => $lim
		));
	} else if ($GLOBALS['plogger_level'] == 'album') {
		if (check_album_id($id)) {
			plogger_init_pictures(array(
			'type' => 'album',
			'value' => $id,
			'from' => $from,
			'limit' => $lim,
			'sortby' => isset($_SESSION['plogger_sortby']) ? $_SESSION['plogger_sortby'] : '',
			'sortdir' => isset($_SESSION['plogger_sortdir']) ? $_SESSION['plogger_sortdir'] : ''
			));
		}
	} else if ($GLOBALS['plogger_level'] == 'collection') {
		if (check_collection_id($id)) {
			plogger_init_albums(array(
			'from' => $from,
			'limit' => $lim,
			'collection_id' => $id,
			'sortby' => !empty($config['album_sortby']) ? $config['album_sortby'] : 'id',
			'sortdir' => !empty($config['album_sortdir']) ? $config['album_sortdir'] : 'DESC'
			));
		}
	} else if ($GLOBALS['plogger_level'] == 'picture') {
		if (check_picture_id($id)) {
			// First let's load the thumbnail of the picture at the correct size
			plogger_init_picture(array(
			'id' => $id
			));
		}
	} else {
		// Show all of the collections
		plogger_init_collections(array(
		'from' => $from,
		'limit' => $lim,
		'sortby' => !empty($config['collection_sortby']) ? $config['collection_sortby'] : 'id',
		'sortdir' => !empty($config['collection_sortdir']) ? $config['collection_sortdir'] : 'DESC'
		));
	}
}

function plogger_init_picture($arr) {
	$id = intval($arr['id']);
	$sql = "SELECT `id`, `parent_album` FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE `id`=".$id;
	$result = run_query($sql);

	unset($_SESSION['require_captcha']);

	$row = mysql_fetch_assoc($result);

	if (!$row) {
		return false;
	}

	// Generate a list of all image id's so proper prev/next links can be created. This should be a
	// fast query, even for big albums.
	$image_list = array();

	$sql = "SELECT `id` FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE `parent_album` = '".$row['parent_album']."'";

	// Determine sort ordering
	switch ($_SESSION['plogger_sortby']) {
		case 'number_of_comments':
			$sql = "SELECT `p`.`id`,
			COUNT(`comment`) AS `num_comments`
			FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `p`
			LEFT JOIN `".PLOGGER_TABLE_PREFIX."comments` AS `c` ON `p`.`id`=`c`.`parent_id`
			WHERE `parent_album`=".$row['parent_album']."
			GROUP BY `p`.`id`
			ORDER BY `num_comments` ";
		break;
		case 'caption':
			$sql .= " ORDER BY `caption` ";
		break;
		case 'date_taken':
			$sql .= " ORDER BY `EXIF_date_taken` ";
		break;
		case 'filename':
			$sql .= " ORDER BY `path` ";
		break;
		case 'date':
			$sql .= " ORDER BY `date_submitted` ";
		break;
		default:
		$sql .= " ORDER BY `id` ";
		break;
	}

	switch (strtoupper($_SESSION['plogger_sortdir'])) {
		case 'ASC':
			$sql .= ' ASC';
		break;
		case 'DESC':
			default:
			$sql .= ' DESC';
		break;
	}

	// If sorting by number_of_comments, we sub-sort by 'id' DESC
	if ($_SESSION['plogger_sortby'] == 'number_of_comments') {
		$sql .= ",`p`.`id` DESC";
	} else if ($_SESSION['plogger_sortby'] == 'date' || $_SESSION['plogger_sortby'] == 'date_taken') {
		$sql .= ", `id` DESC";
	}

	$result = run_query($sql);

	while ($image = mysql_fetch_assoc($result)) {
		$image_list[] = $image['id'];
	}

	$GLOBALS['image_list'] = $image_list;

	// First let's load the thumbnail of the picture at the correct size
	$sql = "SELECT *,
	UNIX_TIMESTAMP(`date_submitted`) AS `unix_date_submitted`,
	UNIX_TIMESTAMP(`EXIF_date_taken`) AS `unix_exif_date_taken`
	FROM `".PLOGGER_TABLE_PREFIX."pictures`
	WHERE `id`=$id";
	$result = run_query($sql);

	$GLOBALS['available_pictures'] = mysql_num_rows($result);
	$GLOBALS['picture_counter'] = 0;
	$GLOBALS['picture_dbh'] = $result;

	// Let's load up the comments for the current picture here as well
	$query = "SELECT *,
	UNIX_TIMESTAMP(`date`) AS `unix_date`
	FROM `".PLOGGER_TABLE_PREFIX."comments`
	WHERE `parent_id`=".intval($id)."
	AND `approved`=1";
	if (isset($arr['comments'])) {
		$query .= " ORDER BY `id` ";
		if ($arr['comments'] == 'ASC') {
			$query .= 'ASC';
		} else {
			$query .= 'DESC';
		}
	}

	// Set up the limits for comments (admin section for now)
	if (isset($arr['from']) && $arr['from'] > 0) {
		$from = $arr['from'];
	} else {
		$from = 0;
	}
	if (isset($arr['limit']) && $arr['limit'] > 0) {
		$limit = $arr['limit'];
	} else {
		$limit = -1;
	}
	// Need pagination for comments (later)
	if (($from >= 0) && ($limit >= 0)) {
		$query .= " LIMIT ".$from.",".$limit;
	}
	$result = run_query($query);

	$GLOBALS['available_comments'] = mysql_num_rows($result);
	$GLOBALS['comment_counter'] = 0;
	$GLOBALS['comment_dbh'] = $result;
}

// arr['type'] id|album|collection|latest
// arr['value'] - argument to

// arr['sortby'] - what field is used for sorting
// arr['sortdir'] - asc|desc

// arr['from'] - where to start in the result set- default to 0
// arr['limit'] - how many pictures to return

function plogger_init_pictures($arr) {
	$sql = " FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `p`";

	// If sorting by number_of_comments, we need to join the comments table
	if (isset($arr['sortby']) && $arr['sortby'] == 'number_of_comments') {
		$sql .= " LEFT JOIN `".PLOGGER_TABLE_PREFIX."comments` AS `c` ON `p`.`id`=`c`.`parent_id`";
	}

	// Right now only single id is supported, maybe I want to specify multiple id's as well
	$value = (isset($arr['value']) && $arr['value'] > 0) ? $arr['value'] : -1;

	switch ($arr['type']) {
		case 'collection':
			$sql .= " WHERE `p`.`parent_collection` = '".$value."'";
			break;
		case 'album':
			$sql .= " WHERE `p`.`parent_album` = '".$value."'";
			break;
		case 'id':
			$sql .= " WHERE `p`.`id` = '".$value."'";
			break;
		case 'latest':
			break;
		default:
			return 0;
	}

	$result = run_query("SELECT COUNT(DISTINCT p.`id`) AS cnt ".$sql);
	$row = mysql_fetch_assoc($result);

	$GLOBALS['total_pictures'] = $row['cnt'];

	// If sorting by number_of_comments, we need group and count the results
	if (isset($arr['sortby']) && $arr['sortby'] == 'number_of_comments') {
		$sql = ",COUNT(`comment`) AS `num_comments` ".$sql." GROUP BY `p`.`id`";
	}

	// Query database and retrieve all pictures within selected album
	// And what about searching? well, what about it ..
	$sort_fields = array(
	'number_of_comments' => 'num_comments',
	'caption' => 'caption',
	'date_taken' => 'EXIF_date_taken',
	'filename' => 'path',
	'date' => 'date_submitted',
	'id' => 'id'
	);

	if (isset($arr['sortby']) && isset($sort_fields[$arr['sortby']])) {
		$sortby = $arr['sortby'];
	} else {
		$sortby = 'id'; // Default sort, is to sort by id
	}

	$sortby = $sort_fields[$sortby];
	$sql .= " ORDER BY `".$sortby."`";

	// Determine sort direction
	if (isset($arr['sortdir']) && (strtoupper($arr['sortdir']) == 'ASC')) {
		$sortdir = ' ASC';
	} else {
		$sortdir = ' DESC'; // Default sort direction is descending
	}
	$sql .= $sortdir;

	// If sorting by number_of_comments, date, or date_taken we sub-sort by 'id'
	if (isset($arr['sortby']) && (
		$arr['sortby'] == 'number_of_comments' ||
		$arr['sortby'] == 'date' ||
		$arr['sortby'] == 'date_taken'
	)) {
		$sql .= ",`p`.`id` ".$sortdir;
	}

	// Set up limits, if needed
	$from = 0;
	$limit = 20; // Default limit if nothing is set
	$max_limit = 1000; // Hard-coded max limit, no matter what you do, you cannot
	// go beyond this limit for number of pictures in thumbnails, slideshow etc.

	if (isset($arr['from']) && $arr['from'] > 0) {
		$from = $arr['from'];
	}

	// Enforce hard-coded max limit if the provided limit is extreme
	if (isset($arr['limit']) && $arr['limit'] > 0 && $arr['limit'] <= $max_limit) {
		$limit = $arr['limit'];
	} elseif (isset($arr['limit']) && $arr['limit'] == -1) {
		$limit = -1; // Set to -1 for slideshow, so limit will not be used
	}

	if (($from >= 0) && ($limit >= 0)) $sql .= " LIMIT ".$from.", ".$limit;

	$result = run_query("SELECT p.*,
	UNIX_TIMESTAMP(`date_submitted`) AS `unix_date_submitted`,
	UNIX_TIMESTAMP(`EXIF_date_taken`) AS `unix_exif_date_taken` ".$sql);

	$GLOBALS['available_pictures'] = mysql_num_rows($result);
	$GLOBALS['picture_counter'] = 0;
	$GLOBALS['picture_dbh'] = $result;
}

// arr['searchterms'] - what to search for, space separates different serach terms
// arr['from'] - where to start in the result set, default 0
// arr['limit'] - and how many items to return, default 20
// arr['sortby'] -
// arr['sortdir'] -

function plogger_init_search($arr) {
	global $PLOGGER_DBH;
	$terms = explode(' ', $arr['searchterms']);
	$from = 0;
	$limit = 20;

	if (isset($arr['from']) && $arr['from'] > 0) {
		$from = $arr['from'];
	}

	// Enforce hard-coded max limit
	if (isset($arr['limit']) && $arr['limit'] > 0 && $arr['limit'] <= 100) {
		$limit = $arr['limit'];
	}

	$query = " FROM `".PLOGGER_TABLE_PREFIX."pictures` p LEFT JOIN `".PLOGGER_TABLE_PREFIX."comments` c
	ON p.`id` = c.`parent_id` ";

	if ((count($terms) != 1) || ($terms[0] != '')) {
		$query .= " WHERE ( ";
		foreach ($terms as $term) {
			$term = mysql_real_escape_string($term);
			$multi_term = explode('+', $term);
			if (count($multi_term)>1) {
				$path = implode("%' AND `path` LIKE '%", $multi_term);
				$description = implode("%' AND `description` LIKE '%", $multi_term);
				$comment = implode("%' AND `comment` LIKE '%", $multi_term);
				$caption = implode("%' AND `caption` LIKE '%", $multi_term);
			} else {
				$path = $description = $comment = $caption = $term;
			}
			$query .= "
			`path` LIKE '%$path%' OR
			`description` LIKE '%$description%' OR
			`comment` LIKE '%$comment%' OR
			`caption` LIKE '%$caption%' OR ";
		}

		$query = substr($query, 0, strlen($query) - 3) .") ";
	} else {
		// No search terms? No results either
		$query .= " WHERE 1 = 0";
	}

	$sort_fields = array('date_submitted','id');
	$sortby = 'date_submitted';

	if (isset($arr['sortby']) && in_array($arr['sortby'], $sort_fields)) {
		$sortby = $arr['sortby'];
	}

	$sortdir = ' DESC';

	if (isset($arr['sortdir']) && 'asc' == $arr['sortdir']) {
		$sortdir = ' ASC';
	}

	$result = run_query("SELECT COUNT(DISTINCT p.`id`) AS cnt ".$query);
	$row = mysql_fetch_assoc($result);

	$GLOBALS['total_pictures'] = $row['cnt'];
	// And I need sort order here as well
	// from and limit too
	$result = run_query("SELECT `caption`,`path`,p.`id`,c.`comment`,
	UNIX_TIMESTAMP(`date_submitted`) AS `unix_date_submitted` ".$query."
	GROUP BY p.`id` ORDER BY `$sortby` $sortdir LIMIT $from, $limit");

	$GLOBALS['available_pictures'] = mysql_num_rows($result);
	$GLOBALS['picture_counter'] = 0;
	$GLOBALS['picture_dbh'] = $result;
}

function plogger_init_collections($arr) {
	$sql = "SELECT COUNT(DISTINCT `parent_collection`) AS `num_items`
	FROM `".PLOGGER_TABLE_PREFIX."pictures`";
	$result = run_query($sql);
	$num_items = mysql_result($result, 0, 'num_items');
	$GLOBALS['total_pictures'] = $num_items;

	// Create a list of all non-empty collections. Could be done with subqueries, but
	// MySQL 4.0 does not support those

	// -1 is just for the case there are no images within collections or albums at all
	$image_collection_count = array(-1 => -1);
	$image_album_count = array(-1 => -1);
	$album_count = array();

	// 1. create a list of all albums with at least one photo
	$sql = "SELECT parent_collection, parent_album, COUNT(*) AS imagecount
	FROM `".PLOGGER_TABLE_PREFIX."pictures` GROUP BY parent_collection, parent_album";
	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$image_collection_count[$row['parent_collection']] = $row['imagecount'];
		$image_album_count[$row['parent_album']] = $row['imagecount'];
	}
	$imlist = join(',', array_keys($image_collection_count));
	$albumlist = join(',', array_keys($image_album_count));

	$cond = '';

	if (empty($arr['all_collections'])) {
		$cond = " WHERE `parent_id` IN ($imlist) AND `id` IN ($albumlist) ";
	}

	$sql = "SELECT parent_id, COUNT(*) AS albumcount
	FROM `".PLOGGER_TABLE_PREFIX."albums`
	$cond
	GROUP BY parent_id";

	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$album_count[$row['parent_id']] = $row['albumcount'];
	}

	$GLOBALS['album_count'] = $album_count;

	// By default only collections with pictures are returned
	// Override that with passing all_collections as an argument to this function
	if (empty($arr['all_collections'])) {
		$where = " WHERE `id` IN ($imlist)";
	} else {
		$where = '';
	}

	if (isset($arr['sortby']) && isset($arr['sortdir'])) {
		$order = " ORDER BY ".$arr['sortby']." ".$arr['sortdir'];
	} else {
		$order = '';
	}

	// I need to determine correct arguments for LIMIT from the given page number
	$from = (isset($arr['from'])) ? $arr['from'] : -1;
	$lim = (isset($arr['limit'])) ? $arr['limit'] : -1;
	if ($from >= 0 && $lim > 0) {
		$limit = ' LIMIT '.$from.', '.$lim;
	} else {
		$limit = '';
	}

	$sql = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."collections`".$where.$order.$limit;
	$result = run_query($sql);

	$GLOBALS['available_collections'] = mysql_num_rows($result);
	$GLOBALS['collection_counter'] = 0;
	$GLOBALS['collection_dbh'] = $result;
}

function plogger_init_albums($arr) {
	$collection_id = intval($arr['collection_id']);

	if ($collection_id == -1) {
		$where = '';
	} else {
		$where = "WHERE `parent_collection` = '$collection_id'";
	}

	$sql = "SELECT COUNT(DISTINCT `parent_album`) AS `num_items`
	FROM `".PLOGGER_TABLE_PREFIX."pictures` $where";

	$result = run_query($sql);
	$num_items = mysql_result($result, 0, 'num_items');

	$GLOBALS['total_pictures'] = $num_items;

	// Create a list of all non-empty albums. Could be done with subqueries, but
	// MySQL 4.0 does not support those

	// -1 is just for the case there are no albums at all. Shouldn't happen if user
	// follows links, but let's deal with it anyway
	$image_count = array(-1 => -1);
	// 1. create a list of all albums with at least one photo
	$sql = "SELECT parent_album, COUNT(*) AS imagecount FROM `".PLOGGER_TABLE_PREFIX."pictures` GROUP BY parent_album";
	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$image_count[$row['parent_album']] = $row['imagecount'];
	}

	$imlist = join(',', array_keys($image_count));
	$where = array();

	if ($collection_id != -1) {
		$where[] = "`parent_id` = '$collection_id'";
	}

	if (empty($arr['all_albums'])) {
		$where[] = "id IN ($imlist)";
	}

	if (!empty($where)) {
		$where = "WHERE ".implode(" AND ", $where);
	} else {
		$where = '';
	}

	if (isset($arr['sortby']) && isset($arr['sortdir'])) {
		$order= " ORDER BY ".$arr['sortby']." ".$arr['sortdir'];
	} else {
		$order = '';
	}

	// I need to determine correct arguments for LIMIT from the given page number
	$from = (isset($arr['from'])) ? $arr['from'] : -1;
	$lim = (isset($arr['limit'])) ? $arr['limit'] : -1;
	if ($from >= 0 && $lim > 0) {
		$limit = ' LIMIT '.$from.', '.$lim;
	} else {
		$limit = '';
	}

	$sql = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."albums`".$where.$order.$limit;

	$result = run_query($sql);

	$GLOBALS['available_albums'] = mysql_num_rows($result);
	$GLOBALS['album_counter'] = 0;
	$GLOBALS['album_dbh'] = $result;

}

function plogger_get_header() {
	global $config;
	if (file_exists(THEME_DIR.'/header.php')) {
		include(THEME_DIR.'/header.php');
	} else {
		include($config['basedir'].'plog-content/themes/default/header.php');
	}
}

function plogger_get_footer() {
	global $config;
	if (file_exists(THEME_DIR.'/footer.php')) {
		include(THEME_DIR.'/footer.php');
	} else {
		include($config['basedir'].'plog-content/themes/default/footer.php');
	}
}

function plogger_download_selected_button() {
	global $config;
	if ($GLOBALS['plogger_level'] != 'picture' && $GLOBALS['plogger_mode'] != 'slideshow' && $GLOBALS['plogger_level'] !='404' && $config['allow_dl']) {
		return '<input id="download_selected_button" class="submit" type="submit" name="download_selected_button" value="'.plog_tr('Download Selected').'" />';
	}
}

function plogger_download_selected_form_start() {
	global $config;
	if ($GLOBALS['plogger_level'] != 'picture' && $GLOBALS['plogger_mode'] != 'slideshow' && $GLOBALS['plogger_level'] !='404' && $config['allow_dl']) {
		return "\n\t" . '<form action="'.$config['gallery_url'].'plog-download.php" method="post">
	<p><input type="hidden" name="dl_type" value="'.$GLOBALS['plogger_level'].'" /></p>' . "\n";
	}
}

function plogger_download_selected_form_end() {
	global $config;
	if ($GLOBALS['plogger_level'] != 'picture' && $GLOBALS['plogger_mode'] != 'slideshow' && $GLOBALS['plogger_level'] !='404' && $config['allow_dl']) {
		return "\n\t" . '</form><!-- /download form -->' . "\n";
	}
}

function plogger_print_button() {
	global $config;
	$id = $GLOBALS['plogger_id'];
	if ($GLOBALS['plogger_level'] == 'picture' && $config['allow_print']) {
		return '<a class="print" href="'.$config['gallery_url'].'plog-print.php?id='.$id.'">'.plog_tr('Print Image').'</a>';
	}
}

function plogger_link_back() {
	return '<a title="'.plog_tr('Powered by Plogger').'" href="http://www.plogger.org/">'.plog_tr('Powered by Plogger').'</a>';
}

function plogger_rss_link() {
	global $config;
	if ($GLOBALS['plogger_level'] != 'picture' || $GLOBALS['available_comments'] > 0) {
		if ($config['use_mod_rewrite']) {
			global $path;
			if (!empty($path)) {
				$rss_link = 'http://'.$_SERVER['HTTP_HOST'].'/'.SmartStripSlashes(substr($path, 1)).'feed/';
			} else {
				$rss_link = $config['gallery_url'].'feed/';
			}
		} else {
			$rss_link = $config['gallery_url'].'plog-rss.php?level='.$GLOBALS['plogger_level'].'&amp;id='.$GLOBALS['plogger_id'];
		}
		// Append the search terms
		if ($GLOBALS['plogger_level'] == 'search' && plogger_has_pictures()) {
			$separator = $config['use_mod_rewrite'] ? '?' : '&amp;';
			$rss_link .= $separator . 'searchterms='.htmlspecialchars($_REQUEST['searchterms']);
		}
		return $rss_link;
	} else {
		return false;
	}
}

function plogger_rss_feed_button() {
	global $config;
	if ($GLOBALS['plogger_mode'] != 'slideshow' && $GLOBALS['plogger_level'] != '404' && plogger_rss_link()) {
		// Change the tooltip message to reflect the nature of the RSS aggregate link.
		if ($GLOBALS['plogger_level'] != '') {
			$rss_tooltip = plog_tr('RSS 2.0 subscribe to').': '.$GLOBALS['plogger_level'];
		} else {
			$rss_tooltip = plog_tr('RSS 2.0 subscribe to all images');
		}
		$rss_link = plogger_rss_link();
		$rss_tag = '<a href="'.$rss_link.'"><img id="rss-image" src="'.$config['gallery_url'].'plog-admin/images/rss.gif" title="'.$rss_tooltip.'" alt="'.plog_tr('RSS 2.0 Feed').'" /></a>';
		return $rss_tag;
	}
}

function plogger_slideshow_link() {
	global $config;
	$id = $GLOBALS['plogger_id'];
	$ss_tag = '';
	if ($GLOBALS['plogger_mode'] != 'slideshow' && $GLOBALS['plogger_level'] != '404') {
		if ($GLOBALS['plogger_level'] == 'album') {
			$ss_url = generate_url('album', $GLOBALS['plogger_id'], array('mode' => 'slideshow'));
			$ss_tag = '<a href="'.$ss_url.'">'.plog_tr('View as Slideshow').'</a>';
		} else if ($GLOBALS['plogger_level'] == 'search' && plogger_has_pictures()) {
			$ss_url = generate_url('search', -1, array('searchterms' => urlencode($_REQUEST['searchterms']), 'mode' => 'slideshow'));
			$ss_tag = '<a href="'.$ss_url.'">'.plog_tr('View as Slideshow').'</a>';
		}
	}
	return $ss_tag;
}

function plogger_slideshow_redirect() {
	global $config;
	$redirect = '';
	// Redirect should only be used for slideshow mode
	if ($GLOBALS['plogger_mode'] == 'slideshow') {
		// Redirect should either be to album or to search
		if ($GLOBALS['plogger_level'] == 'album') {
			$redirect = generate_url('album', $GLOBALS['plogger_id'], NULL, true);
		} else if ($GLOBALS['plogger_level'] == 'search') {
			$redirect = generate_url('search', -1, array('searchterms'=>urlencode($_REQUEST['searchterms'])), true);
		}
	}
	return $redirect;
}

function plogger_sort_control() {
	if ($GLOBALS['plogger_mode'] != 'slideshow')
	return generate_sortby($GLOBALS['plogger_level'], $GLOBALS['plogger_id']).generate_sortdir($GLOBALS['plogger_level'], $GLOBALS['plogger_id']);
}

function plogger_pagination_control($page_range = false) {
	global $config;
	if ($GLOBALS['plogger_mode'] != 'slideshow' && $GLOBALS['plogger_level'] != '404' && $GLOBALS['plogger_level'] != 'picture') {
		$page = isset($_GET['plog_page']) ? intval($_GET['plog_page']) : 1;
		$args = array();
		$level = $GLOBALS['plogger_level'];
		$id = $GLOBALS['plogger_id'];
		switch($level) {
			case 'search':
				$num_items = $GLOBALS['total_pictures'];
				$args['searchterms'] = urlencode($_REQUEST['searchterms']);
				return generate_pagination('search', -1, $page, $num_items, $config['thumb_num'], $args);
				break;
			case 'album':
				$num_items = plogger_album_picture_count();
				break;
			case 'collection':
				$num_items = plogger_collection_album_count();
				break;
			default:
				$level = 'collections';
				$id = 0;
				$num_items = plogger_count_collections();
				break;
		}
		return generate_pagination($level, $id, $page, $num_items, $config['thumb_num'], $args, $page_range);
	}
}

// Generate meta tag keywords and descriptions for SEO
function plogger_generate_seo_meta_tags() {
	global $config;

	switch($GLOBALS['plogger_level']) {
		case 'collection':
			$collection_info = get_collection_by_id($GLOBALS['plogger_id']);
			$keyword = $collection_info['name'];
			$description = $collection_info['description'];
			break;

		case 'album':
			$album_info = get_album_by_id($GLOBALS['plogger_id']);
			$keyword = $album_info['name'];
			$description = $album_info['description'];
			break;

		case 'picture':
			$picture_info = get_picture_by_id($GLOBALS['plogger_id']);
				if (!empty($picture_info['caption'])) {
				$keyword = $picture_info['caption'];
			} else {
				$keyword = basename($picture_info['path']);
			}
			$description = $picture_info['description'];
			break;

		case 'search':
			$keyword = isset($_REQUEST['searchterms']) ? str_replace(' ', ',', $_REQUEST['searchterms']) : '';
			$description = plog_tr('Search results from ').$config['gallery_name'];
			break;

		default:
			$keyword = SmartStripSlashes($config['gallery_name']); // used on gallery entry page
			$description = plog_tr('This is ').$config['gallery_name']; // used on gallery entry page
			break;
		}

	return "\t" . '<meta name="keywords" content="'.htmlspecialchars($keyword).'" />
	<meta name="description" content="'.htmlspecialchars($description).'" />' . "\n";
}

/*** The following functions can only be used inside the Picture loop ***/
function plogger_has_pictures() {
	if (isset($GLOBALS['picture_counter']) && isset($GLOBALS['available_pictures'])) {
		return $GLOBALS['picture_counter'] < $GLOBALS['available_pictures'];
	}
	return false;
}

function plogger_comments_on() {
	global $config;
	return $config['allow_comments'];
}

function plogger_picture_allows_comments() {
	$picture = $GLOBALS['current_picture'];
	$id = $picture['id'];
	return $picture['allow_comments'];
}

function plogger_load_picture() {
	$rv = mysql_fetch_assoc($GLOBALS['picture_dbh']);
	$GLOBALS['picture_counter']++;
	$GLOBALS['current_picture'] = $rv;
	return $rv;
}

function plogger_picture_has_comments() {
	return $GLOBALS['comment_counter'] < $GLOBALS['available_comments'];
}

function plogger_load_comment() {
	$rv = mysql_fetch_assoc($GLOBALS['comment_dbh']);
	$GLOBALS['comment_counter']++;
	$GLOBALS['current_comment'] = $rv;
	return $rv;
}

function plogger_get_comment_date($format = false) {
	global $config;
	$comment = $GLOBALS['current_comment'];
	if (empty($format)) {
		$format = $config['date_format'];
	}
	return date($format, $comment['unix_date']);
}

function plogger_get_comment_id() {
	$comment = $GLOBALS['current_comment'];
	return $comment['id'];
}

function plogger_get_comment_email() {
	$comment = $GLOBALS['current_comment'];
	if ($comment['email'] == '') {
		return '&nbsp;';
	}
	return $comment['email'];
}

function plogger_get_comment_url() {
	$comment = $GLOBALS['current_comment'];
	if ($comment['url'] == '') {
		return '&nbsp;';
	}
	return htmlspecialchars($comment['url']);
}

function plogger_get_comment_author() {
	$comment = $GLOBALS['current_comment'];
	if ($comment['author'] == '') {
		return '&nbsp;';
	}
	return htmlspecialchars(SmartStripSlashes($comment['author']), ENT_QUOTES);
}

function plogger_get_comment_text($specialchars = false) {
	$comment = $GLOBALS['current_comment'];
	if ($comment['comment'] == '') {
		return '&nbsp;';
	}
	return htmlspecialchars(SmartStripSlashes($comment['comment']), ENT_QUOTES);
}

function plogger_get_form_value($value) {
	if (isset($GLOBALS['plogger-form'][$value])) {
		return $GLOBALS['plogger-form'][$value];
	}
	return false;
}

function plogger_get_form_author() {
	if (isset($GLOBALS['plogger-form']['author'])) {
		return $GLOBALS['plogger-form']['author'];
	}
	return false;
}

function plogger_get_form_email() {
	if (isset($GLOBALS['plogger-form']['email'])) {
		return $GLOBALS['plogger-form']['email'];
	}
	return false;
}

function plogger_get_form_url() {
	if (isset($GLOBALS['plogger-form']['url'])) {
		return $GLOBALS['plogger-form']['url'];
	}
	return false;
}

function plogger_get_form_comment() {
	if (isset($GLOBALS['plogger-form']['comment'])) {
		return $GLOBALS['plogger-form']['comment'];
	}
	return false;
}

function plogger_is_form_error($field) {
	if (isset($GLOBALS['plogger-form-error'])) {
		if (in_array(strtolower($field), $GLOBALS['plogger-form-error'])) {
			return true;
		}
	}
	return false;
}

function plogger_get_form_token() {
	// Set the session spam token
	$token = generate_password(8, 32);
	$_SESSION['plogger-token'] = $token;
	return '<input type="hidden" name="plogger-token" value="'.$token.'" />'."\n";
}

function plogger_comment_post_error() {
	if (isset($_SESSION['comment_post_error'])) {
		// Set up the error message arrays
		$GLOBALS['comment_errors'] = $_SESSION['comment_post_error'];
		$GLOBALS['total_comment_errors'] = count($GLOBALS['comment_errors']);
		$GLOBALS['current_comment_error'] = 0;
		unset($_SESSION['comment_post_error']);
		// Set up the form information arrays
		$GLOBALS['plogger-form'] = $_SESSION['plogger-form'];
		$GLOBALS['plogger-form-error'] = $_SESSION['plogger-form-error'];
		unset($_SESSION['plogger-form']);
		unset($_SESSION['plogger-form-error']);
		return true;
	}
	return false;
}

function plogger_has_comment_errors() {
	if (isset($GLOBALS['current_comment_error']) && isset($GLOBALS['total_comment_errors'])) {
		return $GLOBALS['current_comment_error'] < $GLOBALS['total_comment_errors'];
	}
	return false;
}

function plogger_get_comment_error() {
	if (isset($GLOBALS['comment_errors']) && isset($GLOBALS['current_comment_error'])) {
		$error = $GLOBALS['comment_errors'][$GLOBALS['current_comment_error']];
		$GLOBALS['current_comment_error']++;
		return $error;
	}
	return false;
}

function plogger_comment_moderated() {
	if (isset($_SESSION['comment_moderated'])) {
		unset($_SESSION['comment_moderated']);
		return 1;
	} else
	return 0;
}

function plogger_get_picture_filename($specialchars = false) {
	global $config;
	$filename = SmartStripSlashes(basename($GLOBALS['current_picture']['path'])); // Get the basename and clean up the text a little
	$filename = substr($filename, 0, strrpos($filename, '.')); // Remove the file extension
	// If the truncate value is set and the filename is longer than the truncate value,
	// chop it off and add the trailing ellipses
	if (intval($config['truncate']) > 0 && isset($filename{ intval($config['truncate'])})) {
		$filename = substr($filename, 0, intval($config['truncate'])).'...';
	}
	if ($specialchars) {
		return htmlspecialchars($filename, ENT_QUOTES);
	}
	return $filename;
}

function plogger_get_picture_url() {
	$row = $GLOBALS['current_picture'];
	return generate_url('picture', $row['id']);
}

function plogger_get_picture_id() {
	$row = $GLOBALS['current_picture'];
	return $row['id'];
}

function plogger_get_picture_thumb($type = THUMB_SMALL) {
	$pic = $GLOBALS['current_picture'];
	return generate_thumb($pic['path'], $pic['id'], $type);
}

function plogger_get_picture_caption($clean = false) {
	if (!isset($GLOBALS['current_picture']['caption']) || $GLOBALS['current_picture']['caption'] == '') {
		return '&nbsp;';
	}
	switch ($clean) {
		case 'strip':
			return trim(strip_tags(SmartStripSlashes($GLOBALS['current_picture']['caption'])));
			break;
		case 'code':
			return htmlspecialchars(trim(SmartStripSlashes($GLOBALS['current_picture']['caption'])), ENT_QUOTES);
			break;
		case 'clean':
			return htmlspecialchars(trim(strip_tags(SmartStripSlashes($GLOBALS['current_picture']['caption']))), ENT_QUOTES);
			break;
		default:
			return trim(SmartStripSlashes($GLOBALS['current_picture']['caption']));
	}
}

function plogger_get_thumbnail_info() {
	global $thumbnail_config;
	global $config;
	$thumbpath = '';

	// Make sure we generate the thumbnail before we try to get the image information
	switch ($GLOBALS['plogger_level']) {
		case 'collections':
			$thumbpath = plogger_get_collection_thumb();
			break;
		case 'collection':
			$thumbpath = plogger_get_album_thumb();
			break;
		case 'album':
		case 'search':
			$thumbpath = plogger_get_picture_thumb($size = THUMB_SMALL);
			break;
		case 'picture':
			$thumbpath = plogger_get_picture_thumb($size = THUMB_LARGE);
			break;
	}

	// Get the absolute path instead of the URL
	$thumbpath = str_replace($config['gallery_url'], $config['basedir'], $thumbpath);

	if (!is_readable($thumbpath)) {
		// Again, do we want to support video in the Plogger core?
		// $image_info = getimagesize($config['basedir'].'plog-graphics/thumb-video.gif');
		return false;
	} else {
		$image_info = getimagesize($thumbpath);
		$image_info['width'] = $image_info[0];
		$image_info['height'] = $image_info[1];
	}
	return $image_info;
}

function plogger_get_source_picture_path() {
	global $config;
	return $config['basedir'].'plog-content/images/'.SmartStripSlashes($GLOBALS['current_picture']['path']);
}

function plogger_get_picture_description($clean = false) {
	if (!isset($GLOBALS['current_picture']['description']) || $GLOBALS['current_picture']['description'] == '') {
		return '&nbsp;';
	}
	switch ($clean) {
		case 'strip':
			return trim(strip_tags(SmartStripSlashes($GLOBALS['current_picture']['description'])));
			break;
		case 'code':
			return htmlspecialchars(trim(SmartStripSlashes($GLOBALS['current_picture']['description'])), ENT_QUOTES);
			break;
		case 'clean':
			return htmlspecialchars(trim(strip_tags(SmartStripSlashes($GLOBALS['current_picture']['description']))), ENT_QUOTES);
			break;
		default:
			return trim(SmartStripSlashes($GLOBALS['current_picture']['description']));
	}
}

function plogger_picture_comment_count() {
	$row = $GLOBALS['current_picture'];
	$comment_query = "SELECT COUNT(`id`) AS `num_comments` FROM `".PLOGGER_TABLE_PREFIX."comments`
	WHERE approved = 1 AND `parent_id`='".$row['id']."'";
	$comment_result = run_query($comment_query);
	$num_comments = mysql_result($comment_result, 0, 'num_comments');
	return $num_comments;
}

function plogger_get_picture_date($format = '', $submitted = 0) {
	global $config;
	$row = $GLOBALS['current_picture'];
	if ($submitted) {
		$date_taken = $row['unix_date_submitted'];
	} else {
		$date_taken = !empty($row['unix_exif_date_taken']) ? $row['unix_exif_date_taken'] : $row['unix_date_submitted'];
	}
	if (!$format) {
		$format = $config['date_format'];
	}
	return translate_date(date($format, $date_taken));
}

function translate_date($date) {
	$fields = array(
	'January' => plog_tr('January'),
	'February' => plog_tr('February'),
	'March' => plog_tr('March'),
	'April' => plog_tr('April'),
	'May' => plog_tr('May'),
	'June' => plog_tr('June'),
	'July' => plog_tr('July'),
	'August' => plog_tr('August'),
	'September' => plog_tr('September'),
	'October' => plog_tr('October'),
	'November' => plog_tr('November'),
	'December' => plog_tr('December'),

	'Monday' => plog_tr('Monday'),
	'Tuesday' => plog_tr('Tuesday'),
	'Wednesday' => plog_tr('Wednesday'),
	'Thursday' => plog_tr('Thursday'),
	'Friday' => plog_tr('Friday'),
	'Saturday' => plog_tr('Saturday'),
	'Sunday' => plog_tr('Sunday'),
	);

	// Replace English month and day
	foreach($fields as$fkey => $fval) {
		$date = str_replace($fkey, $fval, $date);
	}

	// Replace English month and day using first three letters abbreviation
	foreach($fields as$fkey => $fval) {
		$date = str_replace(substr($fkey, 0, 3), substr($fval, 0, 3), $date);
	}

	return plog_tr($date);
}

function plogger_get_source_picture_url() {
	global $config;
	return (!empty($config['allow_fullpic'])) ? $config['gallery_url'].'plog-content/images/'.SmartStripSlashes($GLOBALS['current_picture']['path']) : '#';
}

/**
* @author derek@plogger.org
* @return string html list of thumbnails
*/

function plogger_get_thumbnail_nav() {
	global $config, $thumbnail_config;
	if($thumbnail_config[THUMB_NAV]['disabled'] == 1) return ''; // Return if thumbnail nav disabled
	$thumb_pic_array = array();
	$image_list = $GLOBALS['image_list'];
	$array_length = count($image_list); // Store array length
	$curr = $GLOBALS['current_picture'];
	$curr_pos = array_search($curr['id'], $image_list);
	$range = (isset($config['thumb_nav_range'])) ? $config['thumb_nav_range'] : 0;
	// If length is 0, use all thumbs
	if($range == 0) {
		// get_picture_by_id modified to take arrays, so pass the whole array
		$thumb_nav_array = $image_list;
		// Else, add a thumb each side of current for each value of $config['thumb_nav_range']
	} else {
		$thumb_nav_array = array($curr['id']);
		$count = 1;
		for($i=1; $i<$range; $i++) {
			// If we're at or over our range, break the loop
			if ($count >= $range) {
				break;
			}
			// Use unshift() to add to the beginning, push() to add to the end
			// Check that we have images on each side
			if($curr_pos - $i >= 0) {
				// Add a previous picture
				if(!empty($image_list[$curr_pos-$i])) array_unshift($thumb_nav_array, $image_list[$curr_pos-$i]);
				$count++;
			}
			if($array_length-1 >= ($curr_pos+$i)) {
				// Add a next picture
				if(!empty($image_list[$curr_pos+$i])) array_push($thumb_nav_array, $image_list[$curr_pos+$i]);
				$count++;
			}
		}
		// Check our range again and use array_shift to remove a single previous picture
		// Should only be a single digit over in case of odd numbered range
		if (count($thumb_nav_array) > $range) {
			array_shift($thumb_nav_array);
		}
	}
	foreach($thumb_nav_array as $thumb_nav_value) {
		$thumb_pic_array[] = get_picture_by_id($thumb_nav_value);
	}
	return plogger_format_thumb_nav($thumb_pic_array);
}

function plogger_format_thumb_nav($thumb_nav_array) {
	$thumb_nav_out = "\t\t\t" . '<ul id="thumb-nav">' . "\n";
	foreach($thumb_nav_array as $current_thumb_nav) {
		unset($title); unset($img_path); unset($class); unset($link); // php has problems with reassigning via iteration
		$title = (!empty($current_thumb_nav['caption'])) ? $current_thumb_nav['caption'] : '';
		$img_path = generate_thumb($current_thumb_nav['path'], $current_thumb_nav['id'], THUMB_NAV);
		if ($current_thumb_nav['id'] == $GLOBALS['current_picture']['id']) {
			$thumb_nav_out .="\t\t\t\t" . '<li class="current"><img src="'.$img_path.'" alt="'.$title.'" /></li>' . "\n";
		} else {
			$link = generate_url('picture', $current_thumb_nav['id']);
			$thumb_nav_out .="\t\t\t\t" . '<li><a href="'.$link.'" title="'.$title.'"><img src="'.$img_path.'" alt="'.$title.'" /></a></li>' . "\n";
		}
	}
	$thumb_nav_out .= "\t\t\t</ul>\n";
	return $thumb_nav_out;
}

function plogger_get_next_picture_url() {
	$image_list = $GLOBALS['image_list'];
	$row = $GLOBALS['current_picture'];
	$current_picture = array_search($row['id'], $image_list);
	$next_link = '';
	if ($current_picture < sizeof($image_list)-1) {
		$next_link = generate_url('picture', $image_list[$current_picture+1]);
	}
	return $next_link;
}

function plogger_get_prev_picture_url() {
	$image_list = $GLOBALS['image_list'];
	$row = $GLOBALS['current_picture'];
	$current_picture = array_search($row['id'], $image_list);
	$prev_link = '';
	if ($current_picture > 0) {
		$prev_link = generate_url('picture', $image_list[$current_picture-1]);
	}
	return $prev_link;
}

/*** End of Picture loop functions ***/

/*** The following functions can only be used inside the Collections loop ***/
function plogger_load_collection() {
	$rv = mysql_fetch_assoc($GLOBALS['collection_dbh']);
	$GLOBALS['collection_counter']++;
	$GLOBALS['current_collection'] = $rv;
	return $rv;
}

function plogger_has_collections() {
	if (isset($GLOBALS['collection_counter']) && isset($GLOBALS['available_collections'])) {
		return $GLOBALS['collection_counter'] < $GLOBALS['available_collections'];
	}
	return false;
}

function plogger_get_collection_url() {
	$row = $GLOBALS['current_collection'];
	return generate_url('collection', $row['id']);
}

function plogger_get_collection_thumb() {
	$rv = $GLOBALS['current_collection'];
	// Figure out the thumbnail as well
	$thumb_query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE ";
	if ($rv['thumbnail_id'] > 0) {
		$thumb_query .= " `id`=".$rv['thumbnail_id'];
	} else {
		$thumb_query .= " `parent_collection`='".$rv['id']."' ORDER BY `id` DESC LIMIT 1";
	}
	$thumb_result = run_query($thumb_query);
	$thumb_data = mysql_fetch_assoc($thumb_result);
	if ($thumb_data) {
		$rv['thumbnail_id'] = $thumb_data['id'];
		$rv['thumbnail_path'] = $thumb_data['path'];
	}
	return generate_thumb($rv['thumbnail_path'], $rv['thumbnail_id'], THUMB_SMALL);
}

function plogger_collection_album_count() {
	if (isset($GLOBALS['album_count']) && isset($GLOBALS['current_collection']['id']) && isset($GLOBALS['album_count'][$GLOBALS['current_collection']['id']])) {
		return $GLOBALS['album_count'][$GLOBALS['current_collection']['id']];
	} else {
		return 0;
	}
}

function plogger_get_collection_description($clean = false) {
	if (!isset($GLOBALS['current_collection']['description']) || $GLOBALS['current_collection']['description'] == '') {
		return '&nbsp;';
	}
	switch ($clean) {
		case 'strip':
			return trim(strip_tags(SmartStripSlashes($GLOBALS['current_collection']['description'])));
			break;
		case 'code':
			return htmlspecialchars(trim(SmartStripSlashes($GLOBALS['current_collection']['description'])), ENT_QUOTES);
			break;
		case 'clean':
			return htmlspecialchars(trim(strip_tags(SmartStripSlashes($GLOBALS['current_collection']['description']))), ENT_QUOTES);
			break;
		default:
			return trim(SmartStripSlashes($GLOBALS['current_collection']['description']));
	}
}

function plogger_get_collection_name($specialchars = false) {
	if ($specialchars) {
		return htmlspecialchars(SmartStripSlashes($GLOBALS['current_collection']['name']), ENT_QUOTES);
	}
	return SmartStripSlashes($GLOBALS['current_collection']['name']);
}

function plogger_get_collection_id() {
	return $GLOBALS['current_collection']['id'];
}

function plogger_count_collections() {
	$numquery = "SELECT COUNT(DISTINCT `parent_collection`) AS `num_collections` FROM `".PLOGGER_TABLE_PREFIX."pictures`";
	$numresult = run_query($numquery);
	$num_albums = mysql_result($numresult, 0, 'num_collections');
	return $num_albums;
}

/*** End of Collection loop functions ***/

/*** The following functions can only be used inside the Albums loop ***/
function plogger_load_album() {
	$rv = mysql_fetch_assoc($GLOBALS['album_dbh']);
	$GLOBALS['album_counter']++;
	$GLOBALS['current_album'] = $rv;
	return $rv;
}

function plogger_has_albums() {
	if (isset($GLOBALS['album_counter']) && isset($GLOBALS['available_albums'])) {
		return $GLOBALS['album_counter'] < $GLOBALS['available_albums'];
	}
	return false;
}

function plogger_get_album_url() {
	$row = $GLOBALS['current_album'];
	return generate_url('album', $row['id']);
}

function plogger_get_album_thumb() {
	$rv = $GLOBALS['current_album'];
	// Figure out the thumbnail as well
	$thumb_query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE ";
	if ($rv['thumbnail_id'] > 0)
	$thumb_query .= " `id`=".$rv['thumbnail_id'];
	else
	$thumb_query .= " `parent_album`='".$rv['id']."' ORDER BY `date_submitted` DESC LIMIT 1";
	$thumb_result = run_query($thumb_query);
	$thumb_data = mysql_fetch_assoc($thumb_result);
	if ($thumb_data) {
		$rv['thumbnail_id'] = $thumb_data['id'];
		$rv['thumbnail_path'] = $thumb_data['path'];
	}
	return generate_thumb($rv['thumbnail_path'], $rv['thumbnail_id'], THUMB_SMALL);
}

function plogger_album_picture_count() {
	if (isset($GLOBALS['current_album'])) {
		$row = $GLOBALS['current_album'];
		// XXX: this may be faster?
		$numquery = "SELECT COUNT(DISTINCT `id`) AS `num_pictures` FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE `parent_album`='".$row['id']."'";
		$numresult = run_query($numquery);
		return mysql_result($numresult, 0, 'num_pictures');
	} else {
		return 0;
	}
}

function plogger_get_album_description($clean = false) {
	if (!isset($GLOBALS['current_album']['description']) || $GLOBALS['current_album']['description'] == '') {
		return '&nbsp;';
	}
	switch ($clean) {
		case 'strip':
			return trim(strip_tags(SmartStripSlashes($GLOBALS['current_album']['description'])));
			break;
		case 'code':
			return htmlspecialchars(trim(SmartStripSlashes($GLOBALS['current_album']['description'])), ENT_QUOTES);
			break;
		case 'clean':
			return htmlspecialchars(trim(strip_tags(SmartStripSlashes($GLOBALS['current_album']['description']))), ENT_QUOTES);
			break;
		default:
			return trim(SmartStripSlashes($GLOBALS['current_album']['description']));
	}
}

function plogger_get_album_name($specialchars = false) {
	if ($specialchars) {
		return htmlspecialchars(SmartStripSlashes($GLOBALS['current_album']['name']), ENT_QUOTES);
	}
	return SmartStripSlashes($GLOBALS['current_album']['name']);
}

function plogger_get_album_id() {
	return $GLOBALS['current_album']['id'];
}

function plogger_get_detail_link() {
	return '<a accesskey="d" title="'.plog_tr('View Image Details').'" href="#" onclick="flip(\'show_info-exif-table\'); return false;">'.plog_tr('View Image Details').'</a>';
}

function plogger_download_checkbox($id, $label = '') {
	global $config;
	if ($config['allow_dl']) {
		return '<input type="checkbox" name="checked[]" value="'.$id.'" />'.$label;
	} else {
		return '';
	}
}

function plogger_get_next_picture_link() {
	global $config;
	$next_url = plogger_get_next_picture_url();
	if ($next_url)
	if ($config['embedded'] == 0) {
		$next_link = '<a id="next-button" accesskey="." href="'.$next_url.'">'.plog_tr('Next').' &raquo;</a>';
	} else {
		$next_link = '<a id="next-button" accesskey="." href="'.$next_url.'">'.plog_tr('Next').' &raquo;</a>';
	} else
		$next_link = '';
	return $next_link;
}

function plogger_get_prev_picture_link() {
	global $config;
	$prev_url = plogger_get_prev_picture_url();
	if ($prev_url)
	if ($config['embedded'] == 0) {
		$prev_link = '<a id="prev-button" accesskey="," href="'.$prev_url.'">&laquo; '.plog_tr('Previous').'</a>';
	} else {
		$prev_link = '<a id="prev-button" accesskey="," href="'.$prev_url.'">&laquo; '.plog_tr('Previous').'</a>';
	} else
		$prev_link = '';
	return $prev_link;
}

/*** End of Album loop functions ***/

function plogger_get_comments($picture_id) {
	$query = "SELECT *
	FROM `".PLOGGER_TABLE_PREFIX."comments`
	WHERE `parent_id`=".intval($picture_id)."
	ORDER BY `date` DESC";
	$result = run_query($query);
	$comments = array();
	while ($row = mysql_fetch_assoc($result)) {
		$comments[$row['id']] = $row;
	}
	return $comments;
}

?>