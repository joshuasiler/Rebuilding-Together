<?php

/* This file handles the generation of print image page. */

include(dirname(__FILE__).'/plog-load-config.php');

$picture = get_picture_by_id(intval($_GET['id']));
$GLOBALS['plogger_level'] = 'picture';
$GLOBALS['plogger_id'] = intval($_GET['id']);
$GLOBALS['plogger_mode'] = 'print';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="<?php echo $language ?>" lang="<?php echo $language ?>" xmlns="http://www.w3.org/1999/xhtml"> 
<head>
<title><?php echo get_head_title() ?></title>
</head>

<body onload="window.print();">

<div><img src="<?php echo $picture['url']; ?>" alt="<?php echo $picture['caption']; ?>" /></div>

<?php close_db(); ?>
</body>
</html>