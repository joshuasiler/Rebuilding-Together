<?php
// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');

global $inHead;

$inHead = '<script type="text/javascript" src="'.$config['gallery_url'].'plog-admin/js/ajax_editing.js"></script>';

$output = "\n\t" . '<h1>'.plog_tr('Manage Feedback').'</h1>' . "\n";

if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'approve-delete') {
		// Here we will determine if we need to perform an approved or delete action.
		$num_items = 0;

		// Perform the delete function on the selected items
		if (isset($_REQUEST['delete_checked'])) {
			if (isset($_REQUEST['selected'])) {
				foreach($_REQUEST['selected'] as $del_id) {
					// Let's build the query string
					$del_id = intval($del_id);
					$query = "DELETE FROM ".PLOGGER_TABLE_PREFIX."comments WHERE `id`= '".$del_id."'";
					$result = run_query($query);
					$num_items++;
				}
				if ($num_items > 0) {
					$text = ($num_items == 1) ? plog_tr('comment') : plog_tr('comments');
					$output .= "\n\t" . '<p class="success">'.sprintf(plog_tr('You have deleted %s successfully'), '<strong>'.$num_items.'</strong> '.$text).'.</p>' . "\n";
				} else {
					$output .= "\n\t" . '<p class="errors">'.plog_tr('Nothing selected to delete').'!</p>' . "\n";
				}
			}

		} else if (isset($_REQUEST['approve_checked'])) {
			// Set the approval bit to 1 for all selected comments
			if (isset($_REQUEST['selected'])) {
				foreach($_REQUEST['selected'] as $appr_id) {
					// Let's build the query string
					$appr_id = intval($appr_id);
					$query = "UPDATE ".PLOGGER_TABLE_PREFIX."comments SET `approved` = 1 WHERE `id`= '".$appr_id."'";
					$result = run_query($query);
					$num_items++;
				}
				if ($num_items > 0) {
					$text = ($num_items == 1) ? plog_tr('comment') : plog_tr('comments');
					$output .= "\n\t" . '<p class="success">'.sprintf(plog_tr('You have approved %s successfully'), '<strong>'.$num_items.'</strong> '.$text).'.</p>' . "\n";
				} else {
					$output .= "\n\t" . '<p class="errors">'.plog_tr('Nothing selected to approve').'!</p>' . "\n";
				}
			}
		}

	} else if ($_REQUEST['action'] == 'edit-comment') {
		// Show the edit form
		$output .= plog_edit_comment_form($_REQUEST['pid']);
		$edit_page = 1;

	} else if ($_REQUEST['action'] == 'update-comment') {
		if (!isset($_REQUEST['cancel'])) {
			// Update comment in database
			$result = update_comment($_POST['pid'], $_POST['author'], $_POST['email'], $_POST['url'], $_POST['comment']);
			if (isset($result['errors'])) {
				$output .= "\n\t" . '<p class="errors">'.$result['errors'].'</p>' . "\n";
			} else if (isset($result['output'])) {
				$output .= "\n\t" . '<p class="success">'.$result['output'].'</p>' . "\n";
			}
		}
	}
}

if (!isset($edit_page)) {
	// Let's iterate through all the content and build a table
	// Set the default level if nothing is specified

	// Handle pagination
	// Let's determine the limit filter based on current page and number of results per page
	if (isset($_REQUEST['entries_per_page'])) {
		$_SESSION['entries_per_page'] = $_REQUEST['entries_per_page'];
	} else if (!isset($_SESSION['entries_per_page'])) {
		$_SESSION['entries_per_page'] = 20;
	}

	$plog_page = isset($_REQUEST['plog_page']) ? $_REQUEST['plog_page'] : 1; // default to the first page

	$first_item = ($plog_page - 1) * $_SESSION['entries_per_page'];
	if ($first_item < 0) {
		$first_item = 0;
	}
	$limit = "LIMIT ".$first_item.", ".$_SESSION['entries_per_page'];

	// Let's generate the pagination menu as well
	$recordCount = "SELECT count(*) AS num_comments FROM ".PLOGGER_TABLE_PREFIX."comments WHERE `approved` = 1";
	$totalRowsResult = mysql_query($recordCount);
	$num_comments = mysql_result($totalRowsResult, 0, 'num_comments');

	$query = "SELECT count(*) AS in_moderation FROM ".PLOGGER_TABLE_PREFIX."comments WHERE `approved` = 0";
	$mod_result = run_query($query);
	$num_comments_im = mysql_result($mod_result, 0, 'in_moderation');

	// Filter based on whether were looking at approved comments or unmoderated comments
	if (isset($_REQUEST['moderate']) && $_REQUEST['moderate'] == 1) {
		$approved = 0;
		$moderate = 1;
	} else {
		$approved = 1;
		$moderate = 0;
	}
	$output .= "\n\t" . '<form id="contentList" action="'.$_SERVER['PHP_SELF'].'?moderate='.$moderate.'" method="post">';

	if ($approved) {
		$pagination_menu = generate_pagination('admin', 'feedback', $plog_page, $num_comments, $_SESSION['entries_per_page']);
	} else {
		$pagination_menu = generate_pagination('admin', 'feedback', $plog_page, $num_comments_im, $_SESSION['entries_per_page'], array('moderate' => 1));
	}
	$pagination_menu = "\n\t\t" . '<div class="pagination">'.$pagination_menu.'</div>';

	// Generate javascript init function for ajax editing
	$query = "SELECT *, UNIX_TIMESTAMP(`date`) AS `date` from ".PLOGGER_TABLE_PREFIX."comments WHERE `approved` = ".$approved." ORDER BY `id` DESC ".$limit;
	$result = run_query($query);
	if (mysql_num_rows($result) > 0) {
		$output .= "\n\t\t" . '<script type="text/javascript">';
		$output .= "\n\t\t\t" . 'Event.observe(window, \'load\', init, false);';
		$output .= "\n\t\t\t" . 'function init() {' . "\n";
		while($row = mysql_fetch_assoc($result)) {
			$output .= "\t\t\t\tmakeEditable('comment-comment-".$row['id']."');
				makeEditable('comment-author-".$row['id']."');
				makeEditable('comment-url-".$row['id']."');
				makeEditable('comment-email-".$row['id']."');\n";
		}
		$output .= "\t\t\t" . '}';
		$output .= "\n\t\t" . '</script>' . "\n";
	}

	$query = "SELECT *, UNIX_TIMESTAMP(`date`) AS `date` from ".PLOGGER_TABLE_PREFIX."comments WHERE `approved` = ".$approved." ORDER BY `id` DESC ".$limit;
	$result = run_query($query);

	$empty = 0;

	if ($result) {
		if (mysql_num_rows($result) == 0) {
			if ($approved) {
				$output .= "\n\t\t" . '<p class="stats-info">'.plog_tr('You have no comments on your gallery').'.</p>';
			} else {
				$output .= "\n\t\t" . '<p class="stats-info">'.plog_tr('You have no comments waiting for approval').'.</p>';
			}
			$empty = 1;
		}
		if ($approved) {
			if ($num_comments_im > 0) {
				$text = ($num_comments_im == 1) ? plog_tr('comment') : plog_tr('comments');
				$output.= "\n\t\t" . '<p class="actions">'.sprintf(plog_tr('You have %s waiting for approval.'), '<strong>'.$num_comments_im.'</strong> '.$text).' <a href="plog-feedback.php?moderate=1"><strong>'.plog_tr('Click here').'</strong></a> '.plog_tr('to review and approve/delete the moderated').' '.$text.'.</p>' . "\n";
			}
		}

		$counter = 0;

		if (!$empty) {
			$output .= "\n\t\t" . '<div class="entries-page">'.generate_pagination_view_menu().'
		</div><!-- /entries-page -->' . "\n";

			$output .= $pagination_menu;
		}

		while($row = mysql_fetch_assoc($result)) {
			// If we're on our first iteration, dump the header
			if ($counter == 0) {
				if ($approved) {
					if ($num_comments > 0) {
						$text = ($num_comments == 1) ? plog_tr('comment') : plog_tr('comments');
						$output .= "\n\n\t\t" . '<div id="comment-count">'.sprintf(plog_tr('You have %s'), '<strong>'.$num_comments.'</strong> '.$text).'.</div>';
					}
				} else {
					if ($num_comments_im > 0) {
						$text = ($num_comments_im == 1) ? plog_tr('comment') : plog_tr('comments');
						$output .= "\n\n\t\t" . '<div id="comment-count">'.sprintf(plog_tr('You have %s awaiting approval'), '<strong>'.$num_comments_im.'</strong> '.$text).'.</div>';
					}
				}

				$output .= "\n\n\t\t" . '<table style="width: 100%;" cellpadding="3" cellspacing="0">
			<tr class="header">
				<th class="table-header-left align-center width-15"><input name="allbox" type="checkbox" onclick="checkAll(document.getElementById(\'contentList\'));" /></th>
				<th class="table-header-middle align-center width-150">'.plog_tr('Thumb').'</th>
				<th class="table-header-middle align-left width-175">'.plog_tr('Author').'/'.plog_tr('Email').'/'.plog_tr('Website').'</th>
				<th class="table-header-middle align-left width-100">'.plog_tr('Date').'</th>
				<th class="table-header-middle align-left">'.plog_tr('Comment').'</th>
				<th class="table-header-right align-center width-100">'.plog_tr('Actions').'</th>
			</tr>';
			}

			foreach ($row as $key => $value) {
				$value = SmartStripSlashes(htmlspecialchars($value));
				if ($value == '') {
					$row[$key] = '&nbsp;';
				}
			}

			if ($counter%2 == 0) {
				$table_row_color = 'color-1';
			} else {
				$table_row_color = 'color-2';
			}

			// Start a new table row (alternating colors)
			$output .= "\n\t\t\t" . '<tr class="'.$table_row_color.'">';

			// Give the row a checkbox
			$output .= "\n\t\t\t\t" . '<td class="align-center width-15"><p class="margin-5"><input type="checkbox" name="selected[]" value="'.$row['id'].'" /></p></td>';

			// Give the row a thumbnail, we need to look up the parent picture for the comment
			$picture = get_picture_by_id($row['parent_id']);
			$thumbpath = generate_thumb($picture['path'], $picture['id'], THUMB_SMALL);

			// Generate XHTML with thumbnail and link to picture view.
			$imgtag = '<img src="'.$thumbpath.'" title="'.htmlspecialchars(strip_tags($picture['caption']), ENT_QUOTES).'" alt="'.htmlspecialchars(strip_tags($picture['caption']), ENT_QUOTES).'" />';
			$output .= "\n\t\t\t\t" . '<td class="align-center width-150"><div class="img-shadow"><a href="'.generate_thumb($picture['path'], $picture['id'], THUMB_LARGE).'" rel="lightbox" title="'.htmlspecialchars($picture['caption'], ENT_QUOTES).'">'.$imgtag.'</a></div></td>';

			// Author / Email / Website
			$output .= "\n\t\t\t\t" . '<td class="align-left width-175">
					<p class="margin-5 no-margin-bottom"><strong>'.plog_tr('Author').':</strong></p>
					<p class="margin-5 no-margin-top" id="comment-author-'.$row['id'].'">'.$row['author'].'</p>
					<p class="margin-5 no-margin-bottom"><strong>'.plog_tr('Email').':</strong></p>
					<p class="margin-5 no-margin-top" id="comment-email-'.$row['id'].'">'.$row['email'].'</p>
					<p class="margin-5 no-margin-bottom"><strong>'.plog_tr('Website').':</strong></p>
					<p class="margin-5 no-margin-top" id="comment-url-'.$row['id'].'">'.$row['url'].'</p>
				</td>';

			// Date
			$output .= "\n\t\t\t\t" . '<td class="align-left width-100"><p class="margin-5">'.date($config['date_format'], $row['date']).'</p></td>';

			// Comment
			$output .= "\n\t\t\t\t" . '<td class="align-left vertical-top"><p class="margin-5" id="comment-comment-'.$row['id'].'">'.$row['comment'].'</p></td>';

			// Actions panel
			$query = "?action=edit-comment&amp;pid=$row[id]";
			$output .= "\n\t\t\t\t" . '<td class="align-center width-100"><p class="margin-5"><a href="'.$_SERVER['PHP_SELF'].$query.'&amp;entries_per_page='.$_SESSION['entries_per_page'].'&amp;moderate='.$moderate.'"><img src="'.$config['gallery_url'].'plog-admin/images/edit.gif" alt="'.plog_tr('Edit').'" title="'.plog_tr('Edit').'" /></a>';
			$output .= '&nbsp;&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?action=approve-delete&amp;delete_checked=1&amp;selected[]='.$row['id'].'&amp;moderate='.$moderate.'" onclick="return confirm(\''.plog_tr('Are you sure you want to delete this comment?').'\');"><img src="'.$config['gallery_url'].'plog-admin/images/x.gif" alt="'.plog_tr('Delete').'" title="'.plog_tr('Delete').'" /></a>';

			if (!$approved) {
				$output .= "\n\t\t\t\t\t" . '&nbsp;&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?action=approve-delete&amp;approve_checked=1&amp;selected[]='.$row['id'].'&amp;moderate=1" onclick="return confirm(\''.plog_tr('Are you sure you want to approve this comment?').'\');"><img src="'.$config['gallery_url'].'plog-admin/images/new_file.gif" alt="'.plog_tr('Approve').'" title="'.plog_tr('Approve').'" /></a>';
			}

			$output .= '</p></td>' . "\n\t\t\t" . '</tr>';
			$counter++;
		}

		if ($counter > 0) {
			$output .= "\n\t\t\t" . '<tr class="footer">
				<td class="invert-selection" colspan="9"><a href="#" onclick="checkToggle(document.getElementById(\'contentList\')); return false;">'.plog_tr('Toggle Checkbox Selection').'</a></td>
			</tr>
		</table>';
		}
	}

	if (!$empty) {
		$output .= "\n\t\t\t" . ''.$pagination_menu;

		$output .= "\n\n\t\t" . '<div id="approve-delete">
			<input type="hidden" name="action" value="approve-delete" />
			<input class="submit-delete" type="submit" name="delete_checked" onclick="return confirm(\''.plog_tr('Are you sure you want to delete the selected comments?').'\');" value="'.plog_tr('Delete Checked').'" />';
	}
	if (!$approved && !$empty) {
		$output .= "\n\t\t\t" . '<input class="submit" type="submit" name="approve_checked" onclick="return confirm(\''.plog_tr('Are you sure you want to approve the selected comments?').'\');" value="'.plog_tr('Approve Checked').'" />';
	}
	if (!$empty) {
		$output .= "\n\t\t" . '</div><!-- /approve-delete -->';
	}
	$output .= "\n\t" . '</form>'. "\n";
}

display($output, 'feedback');

?>