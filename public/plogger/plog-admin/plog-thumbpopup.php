<?php
// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');
require_once(PLOGGER_DIR.'plog-admin/plog-admin-functions.php');

// This script will just show a small preview of the thumbnail in admin view if
// you can't differentiate the small pics.
echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<body>';

$src = $_REQUEST['src'];
$picture = get_picture_by_id($src);
$id = $picture['id'];
$thumbpath = generate_thumb($picture['path'], $picture['id'], THUMB_LARGE);
$thumbdir =  $config['basedir'].'plog-content/thumbs/lrg-$id-'.basename($picture['path']);
list($width, $height, $type, $attr) = getimagesize($thumbdir);

echo '
<script type="text/javascript">
<!--
this.resizeTo('.$width.'+25,'.$height.'+70);
this.moveTo((screen.width-'.$width.')/2, (screen.height-'.$height.')/2);
-->
</script>';

// Generate XHTML with thumbnail and link to picture view
$imgtag = '<img class="photos" src="'.$thumbpath.'" alt="'.$src.'" />';

?>

<?php echo $imgtag; ?>

</body>
</html>