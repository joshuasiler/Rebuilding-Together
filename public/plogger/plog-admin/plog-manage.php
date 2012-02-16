<?php
// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');

global $inHead;

$inHead = '<script type="text/javascript" src="'.$config['gallery_url'].'plog-admin/js/ajax_editing.js"></script>';

function generate_move_menu($level) {
	if ($level == 'albums') { $parent = 'collections'; }
	if ($level == 'pictures') { $parent = 'albums'; }
	$output = "\n\t\t\t" . '<input class="submit" type="submit" name="move_checked" value="'.plog_tr('Move Checked To').'" />';

	if ($level == 'pictures') {
		$albums = get_albums();
		$output .= generate_albums_menu($albums);
	} else {
		$output .= "\n\t\t\t" . '<select class="move-del-manage" id="group_id" name="group_id">';
		$collections = get_collections();
		foreach($collections as $collection) {
			$output .= "\n\t\t\t\t" . '<option value="'.$collection['id'].'">'.SmartStripSlashes($collection['name']).'</option>';
		}
		$output .= "\n\t\t\t" . '</select>';
	}

	return $output;
}

function generate_albums_menu($albums) {
	$output = "\n\t\t\t" . '<select id="group_id" name="group_id">';
	foreach($albums as $album_id => $album) {
		$selected = '';
		// If we are on the current album then set it to be the default option
		if (isset($_REQUEST['albums_menu']) && isset($_REQUEST['new_album_name'])) {
			if ($albums_menu == $album_id || $new_album_name == $album['album_name']) {
				$selected = ' selected="selected"';
			}
		}

		$output .= "\n\t\t\t\t" . '<option value="'.$album_id.'"'.$selected.'>'.SmartStripSlashes($album['collection_name']).': '.SmartStripSlashes($album['album_name']).'</option>';
	}

	$output .= "\n\t\t\t</select>";
	return $output;
}

function generate_breadcrumb_admin($level, $id = 0) {
	switch ($level) {
		case 'collections':
			$breadcrumbs = '<strong>'.plog_tr('Collections').'</strong>';
			break;
		case 'albums':
			$collection = get_collection_by_id($id);
			$collection_name = SmartStripSlashes($collection['name']);
			$breadcrumbs = '<a href="'.$_SERVER['PHP_SELF'].'">'.plog_tr('Collections').'</a> &raquo; <strong>'.$collection_name.'</strong>';
			break;
		case 'pictures':
			$album = get_album_by_id($id);
			$album_link = SmartStripSlashes($album['name']);
			$collection_link = '<a href="'.$_SERVER['PHP_SELF'].'?level=albums&amp;id='.$album['parent_id'].'">'.SmartStripSlashes($album['collection_name']).'</a>';
			$breadcrumbs = '<a href="'.$_SERVER['PHP_SELF'].'">'.plog_tr('Collections').'</a> &raquo; '.$collection_link.' &raquo; '.'<strong>'.$album_link.'</strong>';
			break;
		case 'comments':
			$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE `id`='".$id."'";
			$result = run_query($query);
			$row = mysql_fetch_assoc($result);

			$picture_link = '<strong>'.SmartStripSlashes(basename($row['path'])).'</strong>';
			$album_id = $row['parent_album'];
			$collection_id = $row['parent_collection'];

			$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."albums` WHERE `id`='".$album_id."'";
			$result = run_query($query);
			$row = mysql_fetch_assoc($result);

			$album_link = '<a href="'.$_SERVER['PHP_SELF'].'?level=pictures&amp;id='.$album_id.'">'.SmartStripSlashes($row['name']).'</a>';

			$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."collections` WHERE `id`='".$collection_id."'";
			$result = run_query($query);
			$row = mysql_fetch_assoc($result);

			$collection_link = '<a href="'.$_SERVER['PHP_SELF'].'?level=albums&amp;id='.$collection_id.'">'.SmartStripSlashes($row['name']).'</a>';

			$breadcrumbs = '<a href="'.$_SERVER['PHP_SELF'].'">'.plog_tr('Collections').'</a> &raquo; '.$collection_link.' &raquo; '.$album_link.' &raquo; '.$picture_link.' - '.'<strong>'.plog_tr('Comments').':</strong>';
			break;
		default:
			$breadcrumbs = '<strong>'.plog_tr('Collections').'</strong>';
	}

	return "\n\t\t" . '<div id="breadcrumb_links">'.$breadcrumbs.'</div>';
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$level = (isset($_REQUEST['level']) && $_REQUEST['level'] != '') ? $_REQUEST['level'] : 'collections';

$output = "\n\t" . '<h1>'.plog_tr('Manage Content').'</h1>' . "\n";

global $config;

// Here we will determine if we need to perform any form actions.
if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'move-delete':
			// We're either moving or deleting
			$num_items = 0;
			$action_result = array();

			if (isset($_REQUEST['delete_checked']) ) {
				// Perform the delete function on the selected items
				if (isset($_REQUEST['selected'])) {
					foreach($_REQUEST['selected'] as $del_id) {
						if ($level == 'pictures') {
							$rv = delete_picture($del_id);
						}
						if ($level == 'collections') {
							$rv = delete_collection($del_id);
						}
						if ($level == 'albums') {
							$rv = delete_album($del_id);
						}

						if (isset($rv['errors'])) {
							$output .= "\n\t" . '<p class="errors">'.$rv['errors'].'</p>' ."\n";
						} else {
							$num_items++;
						}
					}

					if ($num_items > 0) {
						$text = ($num_items == 1) ? plog_tr('entry') : plog_tr('entries');
						$output .= "\n\t" . '<p class="success">'.sprintf(plog_tr('You have deleted %s successfully'), '<strong>'.$num_items.'</strong> '.$text).'.</p>' . "\n";
					}
				} else {
					$output .= "\n\t" . '<p class="errors">'.plog_tr('Nothing selected to delete').'!</p>' . "\n";
				}

			} else if (isset($_REQUEST['move_checked'])) {
				if ($level == 'albums') { $parent = 'parent_id'; }
				if ($level == 'pictures') { $parent = 'parent_album'; }

				// Perform the move function on the selected items
				$pid = $_REQUEST['group_id'];

				if (isset($_REQUEST['selected'])) {
					foreach ($_REQUEST['selected'] as $mov_id) {

						// If we are using pictures we need to update the parent_collection as well
						if ($level == 'pictures') {
							$result = move_picture($mov_id, $pid);
							if (empty($result['errors'])) {
								$num_items++;
							} else {
								$output .= "\n\t" . '<p class="errors">'.$result['errors'].'</p>' . "\n";
							}
						} else if ($level == 'albums') {
							// If we are moving entire albums then we need to rename the folder
							// $pid is our target collection id, $mov_id is our source album

							$result = move_album($mov_id, $pid);
							if (empty($result['errors'])) {
								$num_items++;
							} else {
								$output .= "\n\t" . '<p class="errors">'.$result['errors'].'</p>' . "\n";
							}
						}

					}

					if ($num_items > 0) {
						$text = ($num_items == 1) ? plog_tr('entry') : plog_tr('entries');
						$output .= "\n\t" . '<p class="success">'.sprintf(plog_tr('You have moved %s successfully'), '<strong>'.$num_items.'</strong> '.$text).'.</p>' . "\n";
					}
				} else {
					$output .= "\n\t" . '<p class="errors">'.plog_tr('Nothing selected to move').'!</p>' . "\n";
				}
			}
			break;
		case 'edit-picture':
			$level = 'picture';
			// Show the edit picture form
			$photo = get_picture_by_id($id);
			if ($photo['allow_comments'] == 1) $state = 'checked="checked"'; else $state = '';

			$output .= "\n\t\t" . '<form class="edit width-700" action="'.$_SERVER['PHP_SELF'].'?level=pictures&amp;id='.$photo['parent_album'].'" method="post">';

			$thumbpath = generate_thumb(SmartStripSlashes($photo['path']), $photo['id'], THUMB_SMALL);
			$output .= "\n\t\t\t" . '<div style="float: right;"><img src="'.$thumbpath.'" alt="" /></div>
			<div>
				<div class="strong">'.plog_tr('Edit Image Properties').'</div>
				<p>
					<label class="strong" accesskey="c" for="caption">'.plog_tr('<em>C</em>aption').':</label><br />
					<input size="62" name="caption" id="caption" value="'.htmlspecialchars(SmartStripSlashes($photo['caption'])).'" />
				</p>
				<p>
					<label class="strong" for="description">'.plog_tr('Description').':</label><br />
					<textarea name="description" id="description" cols="60" rows="5">'.htmlspecialchars(SmartStripSlashes($photo['description'])).'</textarea>
				</p>
				<p><input type="checkbox" id="allow_comments" name="allow_comments" value="1" '.$state.' /><label class="strong" for="allow_comments" accesskey="w">'.plog_tr('Allo<em>w</em> Comments').'?</label></p>';
			$output .= "\n\t\t\t\t" . '<input type="hidden" name="pid" value="'.$photo['id'].'" />
				<input type="hidden" name="action" value="update-picture" />
				<input class="submit" name="update" value="'.plog_tr('Update').'" type="submit" />
				<input class="submit-cancel" name="cancel" value="'.plog_tr('Cancel').'" type="submit" />
			</div>
		</form>' . "\n";
			$edit_page = 1;
			break;
		case 'edit-album':
			// Show the edit album form
			$output .= plog_edit_album_form($id);
			$edit_page = 1;
			break;
		case 'edit-collection':
			// Show the edit collection form
			$output .= plog_edit_collection_form($id);
			$edit_page = 1;
			break;
		case 'edit-comment':
			// Show the edit comment form
			$output .= plog_edit_comment_form($id);
			$edit_page = 1;
			break;
		case 'update-picture':
			// Update the picture information
			if (!isset($_REQUEST['cancel'])) {
				$allow_comments = (isset($_REQUEST['allow_comments'])) ? $_REQUEST['allow_comments'] : '';
				$action_result = update_picture($_REQUEST['pid'], $_REQUEST['caption'], $allow_comments, $_REQUEST['description']);
			}
			break;
		case 'update-album':
			// Update the album information
			if (!isset($_REQUEST['cancel'])) {
				$action_result = update_album($_POST['pid'], $_POST['name'], $_POST['description'], $_POST['thumbnail_id']);
			}
			break;
		case 'update-collection':
			// Update the collection information
			if (!isset($_REQUEST['cancel'])) {
				$action_result = update_collection($_POST['pid'], $_POST['name'], $_POST['description'], $_POST['thumbnail_id']);
			}
			break;
		case 'update-comment':
			// Update the comment information
			if (!isset($_REQUEST['cancel'])) {
				$action_result = update_comment($_POST['pid'], $_POST['author'], $_POST['email'], $_POST['url'], $_POST['comment']);
			}
			break;
		case 'add-collection':
			// Add a new collection
			$action_result = add_collection($_POST['name'], $_POST['description']);
			break;
		case 'add-album':
			// Add a new album
			$action_result = add_album($_POST['name'], $_POST['description'], $_POST['parent_collection']);
			break;
	}

	if (!empty($action_result['errors'])) {
		// If there are any errors from the actions above, display the errors for the user
		$output .= "\n\t" . '<p class="errors">'.$action_result['errors'].'</p>' . "\n";
	} elseif (!empty($action_result['output'])) {
		// Else if no errors, display the successful output
		$output .= "\n\t" . '<p class="success">'.$action_result['output'].'</p>' . "\n";
	}

}

if (!isset($edit_page)) {
	// Display the gallery statistics on the main page
	if ($level == 'collections') {
		$output .= "\n\t" . '<p class="stats"><strong>'.plog_tr('Gallery Stats:').'</strong> '.plog_tr('You have').' <strong>'.count_collections().'</strong> '.plog_tr('collections, which contain').' <strong>'.count_albums().'</strong> '.plog_tr('albums and').' <strong>'.count_pictures().'</strong> '.plog_tr('images. Users have posted').' <strong>'.count_comments().'</strong> '.plog_tr('comments to your gallery.').'</p>' . "\n";
	}

	// Here we will generate an 'add collection/album' header form
	if ($level == 'collections') {
		$output .= plog_add_collection_form();
	} else if ($level == 'albums') {
		$output .= plog_add_album_form($id);
	}

	// Let's iterate through all the content and build a table
	// Set the default level if nothing is specified

	// Handle pagination
	// Let's determine the limit filter based on current page and number of results per page
	if (isset($_REQUEST['entries_per_page'])) {
		$_SESSION['entries_per_page'] = $_REQUEST['entries_per_page'];
	} else if (!isset($_SESSION['entries_per_page'])) {
		$_SESSION['entries_per_page'] = 20;
	}

	$cond = '';

	// Determine the filtering conditional based on the level and id number
	if ($level == 'albums' || $level == 'comments') {
		$cond = "WHERE `parent_id` = '".intval($id)."'";
	} else if ($level == 'pictures') {
		$cond = "WHERE `parent_album` = '".intval($id)."'";
	}

	$plog_page = isset($_REQUEST['plog_page']) ? $_REQUEST['plog_page'] : 1; // we're on the first page
	$first_item = ($plog_page - 1) * $_SESSION['entries_per_page'];
	if ($first_item < 0) {
		$first_item = 0;
	}
	$limit = "LIMIT ".$first_item.", ".$_SESSION['entries_per_page'];

	// Let's generate the pagination menu as well
	$recordCount = "SELECT COUNT(*) AS num_items FROM ".PLOGGER_TABLE_PREFIX."$level $cond";
	$totalRowsResult = mysql_query($recordCount);
	$totalRows = mysql_result($totalRowsResult, 0, 'num_items');

	$pagination_menu = "\n\t\t" . '<div class="entries-page">'.generate_pagination_view_menu().'
		</div><!-- /entries-page -->
		<div class="pagination">'.generate_pagination('admin', 'manage', $plog_page, $totalRows, $_SESSION['entries_per_page'], array('level' => $level, 'id' => $id)).'</div><!-- /pagination -->';

	$output .= "\n\t\t" . '<form id="contentList" action="'.$_SERVER['PHP_SELF'].'" method="post">';

	$empty = false;

	switch ($level) {
		case 'comments':
			$output .= $pagination_menu.generate_breadcrumb_admin('comments', $id);
			$output .= plog_comment_manager($id, $first_item, $_SESSION['entries_per_page']);
			break;
		case 'pictures':
			$output .= $pagination_menu.generate_breadcrumb_admin('pictures', $id);
			$output .= plog_picture_manager($id, $first_item, $_SESSION['entries_per_page']);
			break;
		case 'albums':
			$output .= $pagination_menu.generate_breadcrumb_admin('albums', $id);
			$output .= plog_album_manager($id, $first_item, $_SESSION['entries_per_page']);
			break;
		case 'collections':
		default:
			$output .= $pagination_menu.generate_breadcrumb_admin('');
			$output .= plog_collection_manager($first_item, $_SESSION['entries_per_page']);
			break;
	}

	if (!$empty) {
		$output .= "\t\t" . '<div class="pagination">'.generate_pagination('admin', 'manage', $plog_page, $totalRows, $_SESSION['entries_per_page'], array('level' => $level, 'id' => $id, 'entries_per_page' => $_SESSION['entries_per_page'])).'</div><!-- /pagination -->
		<div class="move-del-manage">
			<input type="hidden" name="level" value="'.$level.'" />
			<input type="hidden" name="id" value="'.$id.'" />
			<input type="hidden" name="action" value="move-delete" />
			<input class="submit-delete" type="submit" name="delete_checked" onclick=" return confirm(\''.plog_tr('Are you sure you want to delete selected items?').'\');" value="'.plog_tr('Delete Checked').'" />';
		if (!empty($level) && $level != 'collections' && $level != 'comments') {
			$output .= generate_move_menu($level);
		}
		$output .= "\n\t\t" . '</div><!-- /move-del-manage -->';
	}
	$output .= "\n\t\t" . '</form>' . "\n";
}

display($output, 'manage');

?>