<?php
// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');

$output = '';

if (isset($_POST['submit'])) {

	$allow_dl = (isset($_POST['allow_dl'])) ? 1 : 0;
	$allow_comments = (isset($_POST['allow_comments'])) ? 1 : 0;
	$allow_print = (isset($_POST['allow_print'])) ? 1 : 0;
	$use_mod_rewrite = (isset($_POST['use_mod_rewrite'])) ? 1 : 0;
	$square_thumbs = (isset($_POST['square_thumbs'])) ? 1 : 0;
	$generate_intermediate = (isset($_POST['generate_intermediate'])) ? 1 : 0;
	$disable_intermediate = ($generate_intermediate == 1) ? 0 : 1;
	$comments_moderate = (isset($_POST['comments_moderate'])) ? 1 : 0;
	$comments_notify = (isset($_POST['comments_notify'])) ? 1 : 0;
	$allow_fullpic = (isset($_POST['allow_fullpic']) || $generate_intermediate == 0) ? 1 : 0;
	configure_htaccess_fullpic($allow_fullpic);
	$enable_thumb_nav = (isset($_POST['enable_thumb_nav'])) ? 1 : 0;
	$disable_thumb_nav = ($enable_thumb_nav == 1) ? 0 : 1;

	// Verify that gallery URL contains a trailing slash. If not, add one.
	if ($_POST['gallery_url']{ strlen($_POST['gallery_url'])-1} != '/') {
		$_POST['gallery_url'] .= '/';
	}
	// Verify that the gallery URL begins with 'http://' for mod_rewrite 301 redirects
	if (strpos($_POST['gallery_url'], 'http://') === false && strpos($_POST['gallery_url'], 'https://') === false) {
		$_POST['gallery_url'] = 'http://'.$_POST['gallery_url'];
	}

	// Update general settings
	$query = "UPDATE `".PLOGGER_TABLE_PREFIX."config` SET
	`truncate`= '".intval($_POST['truncate'])."',
	`feed_title`= '".mysql_real_escape_string($_POST['feed_title'])."',
	`feed_content` = '".intval($_POST['rss_content'])."',
	`feed_num_entries`= '".intval($_POST['feed_num_entries'])."',
	`allow_dl`= '".intval($allow_dl)."',
	`allow_comments`= '".intval($allow_comments)."',
	`allow_print`= '".intval($allow_print)."',
	`default_sortby`= '".mysql_real_escape_string($_POST['default_sortby'])."',
	`default_sortdir`= '".mysql_real_escape_string($_POST['default_sortdir'])."',
	`album_sortby`= '".mysql_real_escape_string($_POST['album_sortby'])."',
	`album_sortdir`= '".mysql_real_escape_string($_POST['album_sortdir'])."',
	`collection_sortby`= '".mysql_real_escape_string($_POST['collection_sortby'])."',
	`collection_sortdir`= '".mysql_real_escape_string($_POST['collection_sortdir'])."',
	`thumb_num`= '".intval($_POST['thumb_num'])."',
	`compression`= '".intval($_POST['image_quality'])."',
	`admin_username`= '".mysql_real_escape_string($_POST['admin_username'])."',
	`admin_email`= '".mysql_real_escape_string($_POST['admin_email'])."',
	`date_format`= '".mysql_real_escape_string($_POST['date_format'])."',
	`use_mod_rewrite`= '".intval($use_mod_rewrite)."',
	`comments_notify`= '".intval($comments_notify)."',
	`comments_moderate`= '".intval($comments_moderate)."',
	`gallery_url`= '".mysql_real_escape_string($_POST['gallery_url'])."',
	`gallery_name`= '".mysql_real_escape_string($_POST['gallery_name'])."',
	`thumb_nav_range`= '".intval($_POST['thumb_nav_range'])."',
	`allow_fullpic`= '".intval($allow_fullpic)."'";

	// Update password if set and passwords match
	if (trim($_POST['admin_password']) != '') {
		if (trim($_POST['admin_password']) == trim($_POST['confirm_admin_password'])) {
			$query .= ", `admin_password`= '".md5(mysql_real_escape_string(trim($_POST['admin_password'])))."'";
		} else {
			$error_flag = true;
			$output .= '<p class="errors">'.plog_tr('The passwords you entered did not match').'.</p>';
			$output .= '<p class="success">'.plog_tr('Other changes have been applied successfully').'.</p>';
		}
	}

	run_query($query);

	$small_thumbsize = intval($_POST['small_thumbsize']);
	$small_resize = intval($_POST['small_resize']);
	$large_thumbsize = intval($_POST['large_thumbsize']);
	$large_resize = intval($_POST['large_resize']);
	$rss_thumbsize = intval($_POST['rss_thumbsize']);
	$rss_resize = intval($_POST['rss_resize']);
	$nav_thumbsize = intval($_POST['nav_thumbsize']);
	$time = time();

	if ($thumbnail_config[THUMB_SMALL]['size'] != $small_thumbsize || $thumbnail_config[THUMB_SMALL]['resize_option'] != $small_resize) {
		$query = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config`
		SET `max_size` = '$small_thumbsize',
			`update_timestamp` = '$time',
			`resize_option` = '$small_resize'
		WHERE `id` = ".THUMB_SMALL;
		run_query($query);
	}

	if ($thumbnail_config[THUMB_LARGE]['size'] != $large_thumbsize || $thumbnail_config[THUMB_LARGE]['resize_option'] != $large_resize) {
		$query = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config`
		SET `max_size` = '$large_thumbsize',
			`update_timestamp` = '$time',
			`resize_option` = '$large_resize'
		WHERE id = ".THUMB_LARGE;
		run_query($query);
	}

	$query = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config`
	SET disabled = '$disable_intermediate'
	WHERE id = ".THUMB_LARGE;
	run_query($query);

	if ($thumbnail_config[THUMB_RSS]['size'] != $rss_thumbsize || $thumbnail_config[THUMB_RSS]['resize_option'] != $rss_resize) {
		$query = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config`
		SET max_size = '$rss_thumbsize',
			update_timestamp = '$time',
			resize_option = '$rss_resize'
		WHERE id = ".THUMB_RSS;
		run_query($query);
	}

	if ($thumbnail_config[THUMB_NAV]['size'] != $nav_thumbsize) {
		$query = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config`
		SET max_size = '$nav_thumbsize',
			update_timestamp = '$time'
		WHERE id = ".THUMB_NAV;
		run_query($query);
	}

	$query = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config`
	SET disabled = '$disable_thumb_nav'
	WHERE id = ".THUMB_NAV;
	run_query($query);

	// And read the configuration back again
	$config['gallery_url'] = $_POST['gallery_url'];
	$config['use_mod_rewrite'] = (isset($_POST['use_mod_rewrite'])) ? 1 : 0;
	configure_mod_rewrite($config['use_mod_rewrite']);

	if (!isset($error_flag)) $output .= "\n\t" . '<p class="success">'.plog_tr('You have updated your settings successfully').'.</p>' . "\n";

	$_SESSION['msg'] = $output;
	unset($_POST);
	// do a quick refresh to prevent multiple form submits
	header('Location: plog-options.php');
	exit;

}

if (isset($_SESSION['msg'])) {
	$output .= $_SESSION['msg'];
	unset($_SESSION['msg']);
}

$date_formats = array(
	'n.j.Y', // i.e., 6.29.2008
	'j.n.Y', // i.e., 29.6.2008
	'j-m-y', // i.e., 29-6-2008
	'm.d.Y', // i.e., 6.29.2008
	'm-d-Y', // i.e., 6-29-2008
	'm/d/Y', // i.e., 6/29/2008
	'Ymd', // i.e., 20080629
	'F j, Y', // i.e., June 29, 2008
	'd F Y', // i.e., 29 June 2008
	'D, F j, Y', // i.e., Fri, June 29, 2008
	);

$output .= "\n\t" . '<h1>'.plog_tr('General').'</h1>

		<form action="'.$_SERVER['PHP_SELF'].'" method="post">
			<div id="options-section">
				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="gallery_name">'.plog_tr('Gallery Name').':</label><br />('.plog_tr('optional').')</td>
						<td class="right"><input size="40" type="text" id="gallery_name" name="gallery_name" value="'.stripslashes($config['gallery_name']).'" /></td>
					</tr>
					<tr>
						<td class="left"><label for="gallery_url">'.plog_tr('Gallery URL').':</label></td>
						<td class="right"><input size="40" type="text" id="gallery_url" name="gallery_url" value="'.stripslashes($config['gallery_url']).'" /></td>
					</tr>
				</table>

			<h1>'.plog_tr('Admin').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="admin_username">'.plog_tr('Admin Username').':</label></td>
						<td class="right"><input size="40" type="text" id="admin_username" name="admin_username" value="'.$config['admin_username'].'" /></td>
					</tr>
					<tr>
						<td class="left"><label for="admin_email">'.plog_tr('Admin Email Address').':</label></td>
						<td class="right"><input size="40" type="text" id="admin_email" name="admin_email" value="'.$config['admin_email'].'" /></td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="admin_password">'.plog_tr('New Password').':</label></td>
						<td class="right"><input size="40" type="password" id="admin_password" name="admin_password" value="" /></td>
					</tr>
					<tr>
						<td class="left"><label for="confirm_admin_password">'.plog_tr('Confirm New Password').':</label></td>
						<td class="right"><input size="40" type="password" id="confirm_admin_password" name="confirm_admin_password" value="" /></td>
					</tr>
				</table>

			<h1>'.plog_tr('Sort Order').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="default_sortby">'.plog_tr('Image Sort Order').':</label></td>
						<td class="right">';
						$sort_by_fields = array(
						'date' => plog_tr('Date Submitted'),
						'date_taken' => plog_tr('Date Taken'),
						'caption' => plog_tr('Caption'),
						'filename' => plog_tr('Filename'),
						'number_of_comments' => plog_tr('Number of Comments')
						);

						$sort_by_fields_collection = array(
						'id' => plog_tr('Date Created'),
						'name' => plog_tr('Alphabetical')
						);

						$sort_dir_fields = array(
						'ASC' => plog_tr('Ascending'),
						'DESC' => plog_tr('Descending')
						);

						$output .= "\n\t\t\t\t\t\t\t" . '<select style="width: 145px;" id="default_sortby" name="default_sortby">';
						foreach($sort_by_fields as $sort_key => $sort_caption) {
							$selected = ($config['default_sortby'] == $sort_key) ? 'selected="selected" ': '';
							$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
						}
						$output .= "\n\t\t\t\t\t\t\t" . '</select>';
						$output .= "\n\t\t\t\t\t\t\t" . '<select id="default_sortdir" name="default_sortdir">';
						foreach($sort_dir_fields as $sort_key => $sort_caption) {
							$selected = ($config['default_sortdir'] == $sort_key) ? 'selected="selected" ': '';
							$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
						}
						$output .= "\n\t\t\t\t\t\t\t" . '</select>';
						$output .= "\n\t\t\t\t\t\t" . '</td>
					</tr>
					<tr>
						<td class="left"><label for="album_sortby">'.plog_tr('Album Sort Order').':</label></td>
						<td class="right">';
						$output .= "\n\t\t\t\t\t\t\t" . '<select style="width: 145px;" id="album_sortby" name="album_sortby">';
						foreach($sort_by_fields_collection as $sort_key => $sort_caption) {
							$selected = ($config['album_sortby'] == $sort_key) ? 'selected="selected" ': '';
							$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
						}
						$output .= "\n\t\t\t\t\t\t\t" . '</select>';
						$output .= "\n\t\t\t\t\t\t\t" . '<select id="album_sortdir" name="album_sortdir">';
						foreach($sort_dir_fields as $sort_key => $sort_caption) {
							$selected = ($config['album_sortdir'] == $sort_key) ? 'selected="selected" ': '';
							$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
						}
						$output .= "\n\t\t\t\t\t\t\t" . '</select>';
						$output .= "\n\t\t\t\t\t\t" . '</td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="collection_sortby">'.plog_tr('Collection Sort Order').':</label></td>
						<td class="right">';
						$output .= "\n\t\t\t\t\t\t\t" . '<select style="width: 145px;" id="collection_sortby" name="collection_sortby">';
						foreach($sort_by_fields_collection as $sort_key => $sort_caption) {
							$selected = ($config['collection_sortby'] == $sort_key) ? 'selected="selected" ': '';
							$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
						}
						$output .= "\n\t\t\t\t\t\t\t" . '</select>';
						$output .= "\n\t\t\t\t\t\t\t" . '<select id="collection_sortdir" name="collection_sortdir">';
						foreach($sort_dir_fields as $sort_key => $sort_caption) {
							$selected = ($config['collection_sortdir'] == $sort_key) ? 'selected="selected" ': '';
							$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
						}
						$output .= "\n\t\t\t\t\t\t\t" . '</select>';
						$output .= "\n\t\t\t\t\t\t" . '</td>
					</tr>
				</table>

			<h1>'.plog_tr('Front-End Options').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="date_format">'.plog_tr('Date Format').':</label></td>
						<td class="right">
							<select id="date_format" name="date_format">';
							foreach ($date_formats as $format) {
								$output .= "\n\t\t\t\t\t\t\t\t" . '<option value="'.$format.'"';
								if ($config['date_format'] == $format) {
									$output .= ' selected="selected"';
								}
								$output .= '>'.translate_date(date($format)).'</option>';
							}
							$output .= "\n\t\t\t\t\t\t\t" . '</select>
						</td>
					</tr>
					<tr>
						<td class="left"><label for="allow_dl" style="white-space: nowrap;">'.plog_tr('Allow Compressed Downloads').':</label></td>
						<td class="right">';
						$checked = ($config['allow_dl'] == 1) ? 'checked="checked"' : '';
						$output .= '<input type="checkbox" id="allow_dl" name="allow_dl" value="1" '.$checked.' /></td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="allow_print">'.plog_tr('Allow Auto Print').':</label></td>
						<td class="right">';
						$checked = ($config['allow_print'] == 1) ? 'checked="checked"' : '';
						$output .= '<input type="checkbox" id="allow_print" name="allow_print" value="1" '.$checked.' /></td>
					</tr>
					<tr>
						<td class="left"><label for="use_mod_rewrite">'.plog_tr('Generate Cruft-Free URLs').':</label><br />('.plog_tr('requires mod_rewrite').')</td>
						<td class="right">';
						$htaccess_file = $config['basedir'].'.htaccess';
						$checked = ($config['use_mod_rewrite'] == 1) ? 'checked="checked"' : '';
						if (is_writable($htaccess_file)) {
							$output .= '<input type="checkbox" id="use_mod_rewrite" name="use_mod_rewrite" value="1" '.$checked.' />';
						} else {
							$output .= '.htaccess '.plog_tr('is not writable, please check permissions');
						}
					$output .= '</td>
					</tr>
				</table>

			<h1>'.plog_tr('Images').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="image_quality">'.plog_tr('JPEG Image Quality').':</label><br />(1='.plog_tr('worst').', 95='.plog_tr('best').', 75='.plog_tr('default').'</td>
						<td class="right"><input size="5" type="text" id="image_quality" name="image_quality" value="'.$config['compression'].'" /></td>
					</tr>
					<tr>
						<td class="left"><label for="allow_fullpic">'.plog_tr('Allow Full Image Access').':</label><br />('.plog_tr('must be enabled if intermediate images are disabled').'</td>
						<td class="right">';
						$checked = ($config['allow_fullpic'] == 1) ? 'checked="checked"' : '';
						$output .= '<input type="checkbox" id="allow_fullpic" name="allow_fullpic" value="1" '.$checked.' /></td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="truncate">'.plog_tr('Truncate Long Filenames - Length').':</label><br />('.plog_tr('use zero for no truncation').')</td>
						<td class="right"><input size="5" type="text" id="truncate" name="truncate" value="'.$config['truncate'].'" /></td>
					</tr>
				</table>

			<h1>'.plog_tr('Small Thumbnails').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="small_thumbsize">'.plog_tr('Small Thumbnail Maximum Size').':</label><br />('.plog_tr('pixels').')</td>
						<td class="right"><input size="5" type="text" id="small_thumbsize" name="small_thumbsize" value="'.$thumbnail_config[THUMB_SMALL]['size'].'" /></td>
					</tr>
					<tr>
						<td class="left"><label for="small_resize">'.plog_tr('Resize Small Thumbnail To').':</label></td>
						<td class="right">
							<select style="width: 145px;" id="small_resize" name="small_resize">';
							$resize_options = array(
								0 => plog_tr('width'),
								1 => plog_tr('height'),
								2 => plog_tr('longest side'),
								3 => plog_tr('square')
							);
							foreach ($resize_options as $key => $caption) {
								$selected = ($thumbnail_config[THUMB_SMALL]['resize_option'] == $key) ? 'selected="selected" ': '';
								$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$key.'">'.$caption.'</option>';
							}
							// only need 'square' option for small thumbnails
							array_pop($resize_options);
							$output .= '
							</select>
						</td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="thumb_num">'.plog_tr('Number of Thumbnails Per Page').':</label></td>
						<td class="right"><input size="5" type="text" id="thumb_num" name="thumb_num" value="'.$config['thumb_num'].'" /></td>
					</tr>
				</table>

			<h1>'.plog_tr('Intermediate Thumbnails').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="generate_intermediate">'.plog_tr('Generate Intermediate Images').':</label></td>
						<td class="right">';
						$generate_intermediate = ($thumbnail_config[THUMB_LARGE]['disabled'] == 0) ? "checked='checked'" : "";
						$output.='<input type="checkbox" id="generate_intermediate" name="generate_intermediate" value="1" '.$generate_intermediate.' /></td>
					</tr>
					<tr>
						<td class="left"><label for="large_thumbsize">'.plog_tr('Intermediate Image Maximum Size').':</label><br />('.plog_tr('pixels').')</td>
						<td class="right"><input size="5" type="text" id="large_thumbsize" name="large_thumbsize" value="'.$thumbnail_config[THUMB_LARGE]['size'].'" /></td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="large_resize">'.plog_tr('Resize Intermediate Image To').':</label></td>
						<td class="right">
							<select style="width: 145px;" id="large_resize" name="large_resize">';
							foreach ($resize_options as $key => $caption) {
								$selected = ($thumbnail_config[THUMB_LARGE]['resize_option'] == $key) ? 'selected="selected" ': '';
								$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$key.'">'.$caption.'</option>';
							}
							$output .= '
							</select>
						</td>
					</tr>
				</table>

			<h1>'.plog_tr('Navigation Thumbnails').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="enable_thumb_nav">'.plog_tr('Thumbnail Navigation Enabled').':</label></td>
						<td class="right">';
						$checked = ($thumbnail_config[THUMB_NAV]['disabled'] == 0) ? 'checked="checked"' : '';
						$output .= '<input type="checkbox" id="enable_thumb_nav" name="enable_thumb_nav" value="1" '.$checked.' /></td>
					</tr>
					<tr>
						<td class="left"><label for="thumb_nav_range">'.plog_tr('Thumbnail Navigation Range').':</label><br />(0 '.plog_tr('for whole album').')</td>
						<td class="right"><input size="5" type="text" id="thumb_nav_range" name="thumb_nav_range" value="'.$config['thumb_nav_range'].'" /></td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="nav_thumbsize">'.plog_tr('Thumbnail Navigation Size').':</label><br />('.plog_tr('cropped to square').')</td>
						<td class="right"><input size="5" type="text" id="nav_thumbsize" name="nav_thumbsize" value="'.$thumbnail_config[THUMB_NAV]['size'].'" /></td>
					</tr>
				</table>

			<h1>'.plog_tr('Comments').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="allow_comments">'.plog_tr('Allow User Comments').':</label><br />('.plog_tr('will override individual settings if unchecked').')</td>
						<td class="right">';
						$checked = ($config['allow_comments'] == 1) ? 'checked="checked"' : '';
						$output .='<input type="checkbox" id="allow_comments" name="allow_comments" value="1" '.$checked.' /></td>
					</tr>
					<tr>
						<td class="left"><label for="comments_notify" style="white-space: nowrap;">'.plog_tr('Send Email Notification for Comments').':</label><br />('.plog_tr('requires valid email address').')</td>
						<td class="right">';
						$checked = ($config['comments_notify'] == 1) ? 'checked="checked"' : '';
						$output .= '<input type="checkbox" id="comments_notify" name="comments_notify" value="1" '.$checked.' /></td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="comments_moderate">'.plog_tr('Place New Comments Into Moderation').':</label></td>
						<td class="right">';
						$checked = ($config['comments_moderate'] == 1) ? 'checked="checked"' : '';
						$output .= '<input type="checkbox" id="comments_moderate" name="comments_moderate" value="1" '.$checked.' /></td>
					</tr>
				</table>

			<h1>'.plog_tr('RSS Syndication').'</h1>

				<table class="option-table" cellspacing="0">
					<tr class="alt">
						<td class="left"><label for="feed_title">'.plog_tr('RSS Feed Title').':</label></td>
						<td class="right"><input size="40" type="text" id="feed_title" name="feed_title" value="'.stripslashes($config['feed_title']).'" /></td>
					</tr>
					<tr>
						<td class="left"><label for="rss_content">'.plog_tr('RSS Content').':</label></td>
						<td class="right">
							<select id="rss_content" name="rss_content">';
							$rss_content_options = array(
							0 => plog_tr('album / picture'),
							1 => plog_tr('pictures only')
							);
							foreach ($rss_content_options as $key => $caption) {
								$selected = ($config['feed_content'] == $key) ? 'selected="selected" ': '';
								$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$key.'">'.$caption.'</option>';
							}
							$output .= '
							</select>
						</td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="rss_thumbsize">'.plog_tr('RSS Image Maximum Size').':</label><br />('.plog_tr('pixels').')</td>
						<td class="right"><input size="5" type="text" id="rss_thumbsize" name="rss_thumbsize" value="'.$thumbnail_config[THUMB_RSS]['size'].'" /></td>
					</tr>
					<tr>
						<td class="left"><label for="rss_resize">'.plog_tr('Resize RSS Image To').':</label></td>
						<td class="right">
							<select style="width: 145px;" id="rss_resize" name="rss_resize">';
							foreach ($resize_options as $key => $caption) {
								$selected = ($thumbnail_config[THUMB_RSS]['resize_option'] == $key) ? 'selected="selected" ': '';
								$output .= "\n\t\t\t\t\t\t\t\t" . '<option '.$selected.'value="'.$key.'">'.$caption.'</option>';
							}
							$output .= '
							</select>
						</td>
					</tr>
					<tr class="alt">
						<td class="left"><label for="feed_num_entries">'.plog_tr('Number of Items Per Feed').':</label></td>
						<td class="right"><input size="5" type="text" id="feed_num_entries" name="feed_num_entries" value="'.$config['feed_num_entries'].'" /></td>
					</tr>
					<tr>
						<td class="left"></td>
						<td class="right"><input class="submit" type="submit" name="submit" value="'.plog_tr('Update Options').'" /></td>
					</tr>
				</table>

			</div>
		</form>' . "\n";

display($output, 'options');

?>