<?php
//////////////////////////////////////////////////////////////
///  phpThumb() by James Heinrich <info@silisoftware.com>   //
//        available at http://phpthumb.sourceforge.net     ///
//////////////////////////////////////////////////////////////
///                                                         //
// See: phpthumb.readme.txt for usage instructions          //
//                                                         ///
//////////////////////////////////////////////////////////////
//                                                          //
// phpThumb.demo.showpic.php                                //
// James Heinrich <info@silisoftware.com>                   //
// 23 Feb 2004                                              //
//                                                          //
// This code is useful for popup pictures (e.g. thumbnails  //
// you want to show larger, such as a larger version of a   //
// product photo for example) but you don't know the image  //
// dimensions before popping up. This script displays the   //
// image with no window border, and resizes the window to   //
// the size it needs to be (usually better to spawn it      //
// large (600x400 for example) and let it auto-resize it    //
// smaller), and if the image is larger than 90% of the     //
// current screen area the window respawns itself with      //
// scrollbars.                                              //
//                                                          //
// Usage:                                                   //
// window.open('showpic.php?src=big.jpg&title=Big+picture', //
//   'popupwindowname',                                     //
//   'width=600,height=400,menubar=no,toolbar=no')          //
//                                                          //
// See demo linked from http://phpthumb.sourceforge.net    ///
//////////////////////////////////////////////////////////////
?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title><?php echo @$_GET['title']; ?></title>
	<script language="Javascript">
	<!--
	// http://www.xs4all.nl/~ppk/js/winprop.html
	function CrossBrowserResizeInnerWindowTo(newWidth, newHeight) {
		if (self.innerWidth) {
			frameWidth  = self.innerWidth;
			frameHeight = self.innerHeight;
		} else if (document.documentElement && document.documentElement.clientWidth) {
			frameWidth  = document.documentElement.clientWidth;
			frameHeight = document.documentElement.clientHeight;
		} else if (document.body) {
			frameWidth  = document.body.clientWidth;
			frameHeight = document.body.clientHeight;
		} else {
			return false;
		}
		if (document.layers) {
			newWidth  -= (parent.outerWidth - parent.innerWidth);
			newHeight -= (parent.outerHeight - parent.innerHeight);
		}
		// original code
		//parent.window.resizeTo(newWidth, newHeight);

		// fixed code: James Heinrich, 20 Feb 2004
		parent.window.resizeBy(newWidth - frameWidth, newHeight - frameHeight);

		return true;
	}
	// -->
	</script>
</head>
<body style="margin: 0px;">
<?php

if (get_magic_quotes_gpc()) {
	$_GET['src'] = stripslashes($_GET['src']);
}
echo '<img src="'.$_GET['src'].'" border="0">';
flush();
if ($imgdata = @getimagesize($_GET['src'])) {

	// this would be an excellent place to put some caching stuff to avoid re-scanning every picture every time

	// check for maximum dimensions to allow no-scrollbar window
	echo '<script language="Javascript">'."\n";
	echo 'if (((screen.width * 1.1) > '.$imgdata[0].') || ((screen.height * 1.1) > '.$imgdata[1].')) {'."\n";
	// screen is large enough to fit whole picture on screen with 10% margin
	echo 'CrossBrowserResizeInnerWindowTo('.$imgdata[0].', '.$imgdata[1].');'."\n";
	echo '} else {'."\n";
	if (!isset($_REQUEST['norespawn'])) {
		// image is too large for screen: respawn with scrollbars
		echo 'window.open("'.$_SERVER['PHP_SELF'].'?src='.urlencode($_GET['src']).'&title='.urlencode(@$_GET['title']).'&norespawn=1", "respawn", "width="+Math.round(screen.width * 0.9)+",height="+Math.round(screen.height * 0.9)+",resizable=yes,status=no,menubar=no,toolbar=no,scrollbars=yes");'."\n";
		echo 'self.close();'."\n";
	}
	echo '}'."\n";
	echo '</script></body></html>';

} elseif (!isset($_REQUEST['norespawn'])) {

	// cannot determine correct window size, or correct size too large: respawn with scrollbars
	echo '<script language="Javascript">window.open("'.$_SERVER['PHP_SELF'].'?src='.urlencode($_GET['src']).'&title='.urlencode(@$_GET['title']).'&norespawn=1", "respawn", "width=600,height=400,resizable=yes,status=no,menubar=no,toolbar=no,scrollbars=yes"); self.close();</script>';

}

?>
</body>
</html>