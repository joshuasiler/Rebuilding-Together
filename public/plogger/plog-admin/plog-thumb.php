<?php
// This will create a picture thumbnail on disk
// First it will be used for import only.

// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require_once(PLOGGER_DIR.'plog-admin/plog-admin-functions.php');

// Set up the default error message
$found = plog_tr('No such image');

if (empty($_GET['img'])) {
	exit($found);
}

$files = get_files(PLOGGER_DIR.'plog-content/uploads');

$up_dir = PLOGGER_DIR.'plog-content/uploads';

foreach($files as $file) {
	if (md5($file) == $_GET['img']) {
		$rname = substr($file, strlen($up_dir)+1);

		$thumbpath = generate_thumb($up_dir.'/'.$rname, 'import-'.substr(md5($file), 0, 2), THUMB_SMALL);
		$found = '<img src="'.$thumbpath.'" alt="" /></div>';
		//echo "found $relative_name!";
		break;
	}
}

close_db();
close_ftp();
echo $found;

?>