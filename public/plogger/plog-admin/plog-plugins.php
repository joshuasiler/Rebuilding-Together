<?php
// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');

global $config;

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

$output = "\n\t" . '<h1>'.plog_tr('Manage Plugins').'</h1>';

$plugin_dir = $config['basedir'].'plog-content/plugins/';

// Scan list of folders within plugins directory
$plugin_list = read_dir($plugin_dir);
sort($plugin_list);

$output .= "\n\n\t\t" . '<div class="info">

			<p class="no-margin-top">'.plog_tr('Plugins allow you to extend your Plogger gallery by adding new functionality to the gallery itself or by making your gallery content available for use elsewhere on your website.').'</p>

			<p class="no-margin-bottom">'.plog_tr('New plugins should be uploaded to the <span style="color: #800; font-weight: bold;">/plog-content/plugins/</span> directory.').'</p>

		</div><!-- /info-->';

// Output table header
$output .= "\n\n\t\t" . '<table id="plugin-table" cellpadding="4" cellspacing="0" width="100%">
			<tr class="header">
				<th class="table-header-left align-left width-175">'.plog_tr('Plugin').'</th>
				<th class="table-header-middle align-left width-75">'.plog_tr('Version').'</th>
				<th class="table-header-middle align-left">'.plog_tr('Description').'</th>
				<th class="table-header-middle align-left width-100">'.plog_tr('Author').'</th>
				<th class="table-header-right align-left width-100">'.plog_tr('Usage Info').'</th>
			</tr>' . "\n";
$counter = 0;

foreach($plugin_list as $plugin_folder_name) {
	$meta_file = $plugin_folder_name.'meta.php';

	$plugin_folder_basename = basename($plugin_folder_name);

	// Only display plugin as available if meta information exists for it
	if (is_file($meta_file)) {
		// Set up default variables if plugin author forgets a meta input
		$plugin_name = $version = $author = $url = $description = $license = $instructions = '';
		// Pull in meta information
		include($meta_file);

		if ($counter%2 == 0) {
			$table_row_color = 'color-1';
		} else {
			$table_row_color = 'color-2';
		}

		$output .= "\n\t\t\t" . '<tr class="'.$table_row_color.'" id="'.$plugin_folder_basename.'">
				<td class="align-left width-175"><strong>'.$plugin_name.'</strong></td>
				<td class="align-left width-75">'.$version.'</td>
				<td class="align-left">'.$description.'<br />&bull; '.plog_tr('Released under the').' '.$license.'.</td>
				<td class="align-left width-100"><a href="'.$url.'">'.$author.'</a></td>
				<td class="width-100">
					<a id="'.$plugin_folder_basename.'-use" style="display: inline;" href="#'.$plugin_folder_basename.'" onclick="toggle(\''.$plugin_folder_basename.'-code, '.$plugin_folder_basename.'-use, '.$plugin_folder_basename.'-hide\');">'.plog_tr('Use this plugin').'</a>
					<a id="'.$plugin_folder_basename.'-hide" style="display: none;" href="#'.$plugin_folder_basename.'" onclick="toggle(\''.$plugin_folder_basename.'-code, '.$plugin_folder_basename.'-use, '.$plugin_folder_basename.'-hide\');">'.plog_tr('Hide the code').'</a>
				</td>
			</tr>';

		// Display the code to use the plugin
		$output .= "\n\t\t\t" . '<tr class="'.$table_row_color.'" id="'.$plugin_folder_basename.'-code" style="display: none;">
				<td class="align-left width-175">&nbsp;</td>
				<td class="align-left" colspan="4">
					<div class="plugins">
						<p class="no-margin-top"><strong>'.plog_tr('PHP include code').':</strong><br /><span style="color: #264e75; font-weight: bold;">&lt;?php include(\''.$plugin_folder_name.$plugin_folder_basename.'.php\'); ?&gt;</span></p>';
		if (!empty($instructions)) {
			$output .= "\n\t\t\t\t\t" . '<p class="no-margin-bottom"><strong>'.plog_tr('Instructions').':</strong><br />'.$instructions.'</p>';
		}
		$output .= "\n\t\t\t\t\t" . '</div>
				</td>
			</tr>';

		$counter++;
	}

}

$output .= "\n\t\t\t" . '<tr class="footer">
				<td colspan="5" style="padding: 1px;">&nbsp;</td>
			</tr>
		</table>' . "\n";

display($output, 'plugins');

?>