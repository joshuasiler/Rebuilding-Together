<?php
// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');
require_once(PLOGGER_DIR.'plog-admin/plog-admin-functions.php');

$output = '';

$action_result = array();

if ($_POST['action'] == 'update') {

	// What field are we updating?
	$field = $_POST['field'];

	// With what?
	$content = str_replace(array('&nbsp;', '%20', '%26nbsp%3B'), ' ', $_POST['content']);
	$content = trim($content);

	// Now we parse the field to be updated and the id number from the field variable
	$var = explode('-', $field);
	$type = $var[0];
	$field = $var[1];
	$id = $var[2];

	//print "debug: field = ".$field.", content = ".$content.", id = ".$id;

	if ($type == 'picture') {
		$result = update_picture_field($id, $field, $content);
		if ($result['output']) {
			if (empty($content)) {
				$content = '&nbsp;';
			}
			echo stripslashes($content);
		} else {
			echo plog_tr('Error').": ".$result['errors'];
		}
	}
	elseif ($type == 'album') {
		$result = update_album_field($id, $field, $content);
		if ($result['output']) {
			if (empty($content)) {
				$content = '&nbsp;';
			}
			echo stripslashes($content);
		} else {
			echo plog_tr('Error').": ".$result['errors'];
		}
	}
	elseif ($type == 'collection') {
		$result = update_collection_field($id, $field, $content);
		if ($result['output']) {
			if (empty($content)) {
				$content = '&nbsp;';
			}
			echo stripslashes($content);
		} else {
			echo plog_tr('Error').": ".$result['errors'];
		}
	}
	elseif ($type == 'comment') {
		$result = update_comment_field($id, $field, $content);
		if ($result['output']) {
			if (empty($content)) {
				$content = '&nbsp;';
			}
			echo stripslashes($content);
		} else {
			echo plog_tr('Error').": ".$result['errors'];
		}
	}
}

if ($_POST['action'] == 'add-collection') {
	$action_result = add_collection($_POST['name'], $_POST['description']);
	if (empty($action_result['errors'])) {
		$output .= "<script type='text/javascript'>Element.show('add_item_link');Element.hide('add_item_form');Form.reset('add_form');</script>";
	}
}

if ($_POST['action'] == 'list-collections') {
	$output .= plog_collection_manager($_POST['page'], $_SESSION['entries_per_page']);
}

if (!empty($action_result['errors'])) {
	$output .= "\n\t" . '<p class="errors" id="rpc_message">'.$action_result['errors'].'</p>' . "\n";
} elseif (!empty($action_result['output'])) {
	$output .= "\n\t" . '<p class="actions" id="rpc_message">'.$action_result['output'].'</p>' . "\n";
}

close_db();
close_ftp();
echo $output;

?>