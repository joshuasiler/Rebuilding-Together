<?php
// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');

function read_dir($path) {
	$dir_arr = array ();
	$handle = opendir($path);

	while ($file = readdir($handle)) {
		if (is_dir($path.$file) && substr($file, 0, 1) != '.') {
			$dir_arr[] = $path.$file.'/' ;
		}
	}

	return $dir_arr;

}

function check_theme_token($theme) {
	global $config;
	$content = '';

	$comment_file = $config['basedir'].'plog-content/themes/'.$theme.'/comments.php';
	$content = implode('', file($comment_file));
	if (strpos($content, 'plogger_get_form_token') === false) {
		return false;
	}
	return true;
}

$output = "\n\t" . '<h1>'.plog_tr('Manage Themes').'</h1>';

$theme_dir = $config['basedir'].'plog-content/themes/';

// Scan list of folders within theme directory
$theme_list = read_dir($theme_dir);
sort($theme_list);

// Activate new theme by setting configuration dir
if (isset($_REQUEST['activate'])) {
	// Insert into database
	$new_theme_dir = basename($_REQUEST['activate']);
	$metafile = $config['basedir'].'plog-content/themes/'.$new_theme_dir.'/meta.php';

	if (file_exists($metafile)) {
		include($metafile);
		$sql = 'UPDATE '.PLOGGER_TABLE_PREFIX.'config SET `theme_dir` = \''.$new_theme_dir.'\'';
		$name = $theme_name.' '.$version;
		if (mysql_query($sql)) {
			$output .= "\n\n\t\t" . '<p class="success">'.sprintf(plog_tr('Activated new theme %s'), '<strong>'.$name.'</strong>').'</p>';
		} else {
			$output .= "\n\n\t\t" . '<p class="errors">'.plog_tr('Error activating theme').'!</p>';
		}

		// Update config variable if page doesn't refresh
		$config['theme_dir'] = $new_theme_dir;
	} else {
		$output .= "\n\n\t\t" . '<p class="errors">'.plog_tr('No such theme').'</p>';
	}
}

$output .= "\n\n\t\t" . '<div class="info">

			<p class="no-margin-top">'.plog_tr('Themes allow you to change the appearance of your Plogger gallery. New themes should be uploaded to the <span style="color: #800; font-weight: bold;">/plog-content/themes/</span> directory.').'</p>

			<p class="no-margin-bottom">'.plog_tr('To switch to a different theme, click the <span style="color: #800; font-weight: bold;">Activate</span> link in the <strong>Status</strong> column. You will need to reload your gallery page to see the changes.').'</p>

		</div><!-- /info-->';

// Output table header
$output .= "\n\n\t\t" . '<table id="theme-table" cellpadding="3" cellspacing="0" width="100%">
			<tr class="header">
				<th class="table-header-left align-center width-175">'.plog_tr('Preview').'</th>
				<th class="table-header-middle align-left width-100">'.plog_tr('Theme').'</th>
				<th class="table-header-middle align-left">'.plog_tr('Description').'</th>
				<th class="table-header-middle align-left width-100">'.plog_tr('Author').'</th>
				<th class="table-header-right align-left width-100">'.plog_tr('Status').'</th>
			</tr>';
$counter = 0;

foreach($theme_list as $theme_folder_name) {
	$meta_file = $theme_folder_name.'meta.php';

	$theme_folder_basename = basename($theme_folder_name);

	// Only display theme as available if meta information exists for it
	if (is_file($meta_file)) {
		// Pull in meta information
		include($meta_file);

		if ($counter%2 == 0) {
			$table_row_color = 'color-1';
		} else {
			$table_row_color = 'color-2';
		}

		// Generate small preview thumb, update thumb if preview.png has been updated
		$timestamp = @filemtime($theme_dir.$theme_folder_basename.'/preview.png');
		$thumbnail_config[THUMB_THEME]['timestamp'] = $timestamp;
		$thumbnail_config[THUMB_THEME]['size'] = 150;
		$preview_thumb = generate_thumb($theme_folder_name.'preview.png', $theme_name, THUMB_THEME);

		// Generate large Lightbox preview thumb, update thumb if preview.png has been updated
		$thumbnail_config[THUMB_THEME]['size'] = 500;
		$preview_thumb_large = generate_thumb($theme_folder_name.'preview.png', $theme_name.'-large', THUMB_THEME);

		// Start a new table row (alternating colors)
		if ($config['theme_dir'] == $theme_folder_basename) {
			$table_class = 'activated';
		} else {
			$table_class = $table_row_color;
		}
		$output .= "\n\t\t\t" . '<tr class="'.$table_class.'">';

		$output .= "\n\t\t\t\t" . '<td class="width-175">';

		if ($preview_thumb) {
			$output .= '<div class="img-shadow"><a rel="lightbox" href="'.$preview_thumb_large.'"><img src="'.$preview_thumb.'" alt="'.$theme_name.'" /></a></div>';
		}

		$output .= '</td>
				<td class="align-left width-100"><strong>'.$theme_name.'</strong><br />Version '.$version.'</td>
				<td style="padding-right: 50px;">'.$description.'<br />&bull; '.plog_tr('Released under the').' '.$license.'.</td>
				<td class="align-left width-100"><a href="'.$url.'">'.$author.'</a></td>';

		if ($config['theme_dir'] == $theme_folder_basename) {
			$output .= "\n\t\t\t\t" . '<td class="active width-100">'.plog_tr('Current').'</td>';
		} else {
			$output .= "\n\t\t\t\t" . '<td class="width-100"><a href="'.$config['gallery_url'].'plog-admin/plog-themes.php?activate='.$theme_folder_basename.'">'.plog_tr('Activate').'</a></td>';
		}

		$output .= "\n\t\t\t" . '</tr>';

		if (!check_theme_token($theme_folder_basename)) {
			$output .= "\n\t\t\t" . '<tr class="'.$table_class.'" id="'.$theme_folder_basename.'-error">
				<td class="align-left" colspan="5">
					<div class="errors">
						<p class="no-margin-top no-margin-bottom">'.sprintf(plog_tr('The spam token could not be found in this theme. Please include the code %s between the opening %s tag and the closing %s tag in the theme file %s'), ' <span style="color: #264e75; font-weight: bold;">&lt;?php echo plogger_get_form_token(); ?&gt;</span>', '&lt;form&gt;', '&lt;/form&gt;', '<strong>'.'plog-content/themes/'.$theme_folder_basename.'/comments.php</strong>').'</p>
					</div>
				</td>
			</tr>';
		}

		$counter++;
	}

}

$output .= "\n\t\t\t" . '<tr class="footer">
				<td colspan="5" style="padding: 1px;">&nbsp;</td>
			</tr>
		</table>' . "\n";

display($output, 'themes');

?>