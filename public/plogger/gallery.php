<?php
/* This is a backwards compatible file to make old installations work correctly */

include_once(dirname(__FILE__).'/plogger.php');

function the_gallery_head() {
	return the_plogger_head();
}

function the_gallery() {
	return the_plogger_gallery();
}

?>