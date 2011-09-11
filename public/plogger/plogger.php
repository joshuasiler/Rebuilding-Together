<?php
global $config, $thumbnail_config;

include_once(dirname(__FILE__).'/plog-load-config.php');

if (!empty($_POST['comment_post_ID'])) {
	// Backward compatibility for SVN themes
	include_once(PLOGGER_DIR.'plog-comment.php');
	exit();
}

// Process path here - is set if mod_rewrite is in use
if (!empty($_REQUEST['path'])) {
	// The following line calculates the path in the album and excludes any subdirectories if
	// Plogger is installed in one
	$path = join('/', array_diff_assoc(explode('/', $_SERVER['REQUEST_URI']), explode('/', $_SERVER['PHP_SELF'])));
	if ($path{ strlen($path)-1} == '/') {
		$path = substr($path, 0, -1);;
	}
	$resolved_path = resolve_path($path);
	if (is_array($resolved_path)) {
		if (isset($resolved_path['level'])) {
			$_GET['level'] = $resolved_path['level'];
		}
		if (isset($resolved_path['id'])) {
			$_GET['id'] = $resolved_path['id'];
		}
		if (isset($resolved_path['plog_page'])) {
			$_GET['plog_page'] = $resolved_path['plog_page'];
		}
		if (isset($resolved_path['mode'])) {
			$_GET['mode'] = $resolved_path['mode'];
		}
		if (isset($resolved_path['searchterms'])) {
			$_REQUEST['searchterms'] = $resolved_path['searchterms'];
		}

		// Get the path for RSS links (maybe should rework this)
		$parts = parse_url($_SERVER['REQUEST_URI']);
		$path = $parts['path'];
	}
} else {
	// Check for additional $_GET query arguments (non-Plogger)
	$query_args = array();
	$query_parts = array();
	$parts = parse_url($_SERVER['REQUEST_URI']);
	if (isset($parts['query'])) {
		parse_str($parts['query'], $query_parts);
	}
	$plogger_args = array('level', 'id', 'mode', 'plog_page', 'searchterms', 'sortby', 'sortdir');
	$url_args = array_keys($query_parts);
	$diff_args = array_diff($url_args, $plogger_args);
	foreach ($diff_args as $diff) {
		$query_args[] = $diff.'='.$query_parts[$diff];
	}
	if (!empty($query_args)) {
		$config['query_args'] = implode('&amp;', $query_args);
	}
}

// Set sorting session variables if they are passed
if (isset($_GET['sortby'])) {
	$_SESSION['plogger_sortby'] = $_GET['sortby'];
}

if (isset($_GET['sortdir'])) {
	$_SESSION['plogger_sortdir'] = $_GET['sortdir'];
}

// The three GET parameters that it accepts are
// $level = 'collection', 'album', or 'picture'
// $id = id number of collection, album, or picture
// $n = starting element (for pagination) go from n to n + max_thumbs (in global config)

// Use Plogger-specific variables to avoid name clashes if Plogger is embedded

$GLOBALS['plogger_level'] = isset($_GET['level']) ? $_GET['level'] : '';
$GLOBALS['plogger_id'] = isset($_GET['id']) ? intval($_GET['id']) : 0;
$GLOBALS['plogger_mode'] = isset($_GET['mode']) ? $_GET['mode'] : '';

// Count collections and albums with pictures in them
$active = get_active_collections_albums();

// If only 1 active album, truncate the 'collection' & 'collections' portion of Plogger
if (count($active['albums']) == 1) {
	$config['truncate_breadcrumb'] = 'album';
	if ($GLOBALS['plogger_level'] != 'picture' && $GLOBALS['plogger_level'] != '404') {
		if ($GLOBALS['plogger_level'] != 'search') {
			$GLOBALS['plogger_level'] = 'album';
		}
		$GLOBALS['plogger_id'] = $active['albums'][0];
	}
// If only 1 active collection, truncate the 'collections' portion of Plogger
} else if (count($active['collections']) == 1) {
	$config['truncate_breadcrumb'] = 'collection';
	if ($GLOBALS['plogger_level']!='album' && $GLOBALS['plogger_level'] != 'picture' && $GLOBALS['plogger_level'] != '404') {
		if ($GLOBALS['plogger_level'] != 'search') {
			$GLOBALS['plogger_level'] = 'collection';
		}
		$GLOBALS['plogger_id'] = $active['collections'][0];
	}
} else {
	$config['truncate_breadcrumb'] = false;
}

$allowed_levels = array('collections', 'collection', 'album', 'picture', 'search', '404');
if (!in_array($GLOBALS['plogger_level'], $allowed_levels)) {
	$GLOBALS['plogger_level'] = 'collections';
}

define('THEME_DIR', PLOGGER_DIR.'plog-content/themes/'.$config['theme_dir']);
define('THEME_URL', $config['theme_url']);

// Initialize Plogger
plogger_init();

// Throw 404 headers if a 404 error has occurred
if ($GLOBALS['plogger_level'] == '404' && !headers_sent()) {
	header( 'Status: 404 Not Found' );
	header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
}

function the_plogger_head() {
	plogger_head();

	$use_file = 'head.php';
	if (file_exists(THEME_DIR.'/'.$use_file)) {
		include(THEME_DIR.'/'.$use_file);
	} else {
		include(PLOGGER_DIR.'plog-content/themes/default/'.$use_file);
	}
}

function the_plogger_gallery() {
	// Collections mode (show all albums within a collection) it's the default
	$use_file = 'collections.php';
	if ($GLOBALS['plogger_level'] == 'picture') {
		$use_file = 'picture.php';
	} elseif ($GLOBALS['plogger_level'] == 'search') {
		if ($GLOBALS['plogger_mode'] == 'slideshow') {
			$use_file = 'slideshow.php';
		} else {
			$use_file = 'search.php';
		}
	} elseif ($GLOBALS['plogger_level'] == 'album') {
		// Album level display mode (display all pictures within album)
		if ($GLOBALS['plogger_mode'] == 'slideshow') {
			$use_file = 'slideshow.php';
		} else {
			$use_file = 'album.php';
		}
	} else if ($GLOBALS['plogger_level'] == 'collection') {
		$use_file = 'collection.php';
	} else if ($GLOBALS['plogger_level'] == '404') {
		$use_file = '404.php';
	}

	// If the theme does not have the requested file, then use the one from the default template
	if (file_exists(THEME_DIR.'/'.$use_file)) {
		include(THEME_DIR.'/'.$use_file);
	} else {
		include(PLOGGER_DIR.'/plog-content/themes/default/'.$use_file);
	}

	// Close the connections
	close_db();
	if (function_exists('close_ftp')) {
		close_ftp();
	}

	// Debug dump at bottom
	if (defined('PLOGGER_DEBUG') && PLOGGER_DEBUG == '1') {
		trace('Queries: '.$GLOBALS['query_count']);
		foreach ($GLOBALS['queries'] as $q) {
			trace($q);
		}
		trace(plog_timer('end'));
	}
}

?>