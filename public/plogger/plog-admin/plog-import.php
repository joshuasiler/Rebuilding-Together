<?php
/* Code by Mike Johnson -- mike@solanosystems.com October 23rd, 2004.
 This is the main administrative interface code. The initial tab is UPLOAD function. */

// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');

function generate_albums_menu($albums, $type = 'multiple', $preselect) {
	$output = '';

	if ($type == 'multiple')
	$output .= '<select name="destinations[]" onclick="var k=document.getElementsByName(\'destination_radio\');k[0].checked=true;" >';
	else
	$output .= '<select name="destination" onclick="var k=document.getElementsByName(\'destination_radio\');k[0].checked=true;" >';
	foreach($albums as $album_id => $album_data) {
		if ($preselect == $album_id)
		$selected = ' selected="selected"'; else $selected = '';
		$output .= "\n\t\t\t\t\t" . '<option value="'.$album_id.'"'.$selected.'>'.$album_data['collection_name'].': '.$album_data['album_name'].'</option>';
	}
	$output .= "\n\t\t\t\t</select>";

	return $output;
}

function generate_collections_menu() {
	$collections = get_collections();
	$output = '<select name="collections_menu" id="collections_menu" onclick="var k=document.getElementsByName(\'destination_radio\');k[1].checked=true;" >';
	foreach($collections as $collection) {
		$output .= "\n\t\t\t\t\t" . '<option value="'.$collection['id'].'">'.$collection['name'].'</option>';
	}
	$output .= "\n\t\t\t\t</select>";

	return $output;
}

$output = '';

$counter = $imported = 0;

// See if the 'nojs' flag has been set if javascript disabled and create query & separator strings for URLs
$query = (isset($_GET['nojs'])) ? '?nojs='.$_GET['nojs'] : '';
$sep = (isset($_GET['nojs'])) ? '&amp;' : '?';

// Check if update has been clicked, handle erroneous conditions, or upload
//print_r($_POST);

if (isset($_POST['upload'])) {

	$destinations = isset($_POST['destinations']) ? $_POST['destinations'] : '';
	$captions = $_POST['captions'];
	$descriptions = $_POST['descriptions'];
	$allow_comments = isset($_POST['allow_comments']) ? $_POST['allow_comments'] : array();
	$files = isset($_POST['files']) ? $_POST['files'] : '';
	$selected = $_POST['selected'];

	global $config;

	$files = get_files($config['basedir'].'plog-content/uploads');

	if ($_POST['destination_radio'] == 'new' && $_POST['new_album_name'] == '') {
		$output .= "\n\t" . '<p class="errors">'.plog_tr('New album name not specified').'!</p>' . "\n";
	} else {

		if ($_POST['destination_radio'] == 'new') {
			// Create the new album
			$result = add_album($_POST['new_album_name'], NULL, $_POST['collections_menu']);
			if (!$result['errors']) {
				// No errors, add images to new album
				$album_id = $result['id'];
			} else {
				// Errors exist, let's find out what they are
				if (isset($result['output']) && $result['output'] == 'existing' && isset($result['id'])) {
					// Album already exists so try to insert images into the existing album
					// and alert the user that their "new" album already exists
					$album_id = $result['id'];
					// Get the collection name for display
					$sql = "SELECT `name` FROM ".PLOGGER_TABLE_PREFIX."collections WHERE id = ".intval($_REQUEST['collections_menu']);
					$result = run_query($sql);
					$row = mysql_fetch_assoc($result);
					$output .= "\n\t" . '<p class="actions">'.sprintf(plog_tr('Album already exists. Uploading file to existing album %s in collection %s'), '<strong>'.$_REQUEST['new_album_name'].'</strong>', '<strong>'.$row['name'].'</strong>').'</p>' . "\n";
				} else {
					// Error has nothing to do with an existing album, show the returned error
					$album_id = '';
					$output .= "\n\t" . '<p class="errors">'.$result['errors'].'</p>' . "\n";
				}
			}
		} else {
			// Use an existing album
			$album_id = $_POST['destination'];
		}

		if ($album_id) {
			foreach($files as $file) {
				$file_key = md5($file);
				if (in_array($file_key, $selected)) {

					$file_name = SmartStripSlashes($file);
					// fully qualified file name
					//$fqfn = $config['basedir'].'plog-content/uploads/'.$file_name;
					$fqfn = $file;

					if (is_file($fqfn)) {
						if (in_array($file_key, $allow_comments)) {
							$allow_comment = 1;
						} else {
							$allow_comment = 0;
						}
						$result = add_picture($album_id,$fqfn, basename($file_name), $captions[$file_key], $descriptions[$file_key], $allow_comment);
						if ($result['picture_id'] !== false) {
							$imported++;
							// Delete thumbnail file if it exists
							$thumbpath = $config['basedir'].'plog-content/thumbs/uploads/import-'.substr($file_key, 0, 2).'-'.basename($file_name);
							if (file_exists($thumbpath) && is_readable($thumbpath)) {
								kill_file($thumbpath);
							}
						}
					}

					$counter++;
				}

			}

			// Get album name for display
			$sql = "SELECT name FROM ".PLOGGER_TABLE_PREFIX."albums WHERE id = $album_id";
			$result = run_query($sql);
			$row = mysql_fetch_assoc($result);

			$output .= "\n\t" . '<h1>'.plog_tr('Import').'</h1>';

			if ($imported > 0) {
				$text = ($imported == 1) ? plog_tr('image was') : plog_tr('images were');
				$output.= "\n\n\t" . '<p class="success width-700">'.sprintf(plog_tr('%s successfully imported to album %s'), '<strong>'.$imported.'</strong> '.$text, '<strong>'.$row['name'].'</strong>').'.</p>'. "\n";
			}

			if ($imported == 0) {
				$output .= "\n\t" . '<h1>'.plog_tr('Import').'</h1>

	<p class="errors">'.plog_tr('Use your FTP client to CHMOD your newly created folders within the <strong>plog-content/uploads/</strong> directory with the proper permissions, or else Plogger cannot access them. Plogger cannot CHMOD the directory for you while PHP is in safe_mode.').'</p>' . "\n";
			}
			/* what is this for?
			else {
				$output .= "\n\t" . '<p class="errors">'.$result['output'].'</p>' . "\n";
			}*/
		}
	}

	// Read the list again, so any newly created directories show up
	// grab both the image files and the folders so we can clean empty folders
	$content = get_files($config['basedir'].'plog-content/uploads', false, true);
	$files = $content['files'];
	$folders = $content['folders'];

	// Build a list of unique directories from the filenames
	$directories = array();
	foreach ($files as $file) {
		$dirname = dirname($file);
		if (!in_array($dirname, $directories)) {
			$directories[md5($dirname)] = $dirname;
		}
	}

	// Clean up empty directories - compare our original $folders array to the new unique $directories array
	foreach ($folders as $folder) {
		if (!in_array($folder, $directories)) {
			kill_dir($folder);
		}
	}

	// Here we will check which group of pictures we are editing, grouped by directory
	if (count($directories) > 0) {
		$output .= "\n\t" . '<div class="actions width-700">'.plog_tr('Would you like to import anything else?');

		$output .= "\n\t\t" . '<ul style="list-style-type: none;">';

		foreach ($directories as $dirkey => $group) {
			$output .= "\n\t\t\t" . '<li class="margin-5"><a class="folder" href="'.$_SERVER['PHP_SELF'].$query.$sep.'directory='.$dirkey.'">'.basename($group).'</a></li>';
		}

		$upload_directory = $config['basedir'].'plog-content/uploads';
		$dirkey = md5($upload_directory);
		$output .= "\n\t\t\t" . '<li class="margin-5"><a class="folder" href="'.$_SERVER['PHP_SELF'].$query.$sep.'directory='.$dirkey.'">'.plog_tr('All Pictures').'</a></li>';
		$output .= "\n\t\t</ul>\n\t</div>\n";

	} else {
		$output .= "\n\n\t\t" . '<div class="actions width-700">'.plog_tr('No images found in the <strong>plog-content/uploads/</strong> directory. To mass import pictures into your gallery, simply:').'
			<ul>
				<li><strong>'.plog_tr('Open an FTP connection</strong> to your website').'</li>
				<li>'.plog_tr('Transfer images you wish to publish to the <strong>plog-content/uploads/</strong> directory').'</li>
				<li>'.plog_tr('Optionally, you can create folders within that directory to import in groups').'</li>
			</ul>
		</div>' . "\n";
	}

} else {
	$output .= "\n\t" . '<h1>'.plog_tr('Import Images').'</h1>';

	$upload_directory = $config['basedir'].'plog-content/uploads';
	if (!is_safe_mode() && !is_writable($upload_directory)) {
		$output .= "\n\n\t" . '<p class="errors">'.plog_tr('Your <strong>plog-content/uploads/</strong> directory is NOT WRITABLE! Use your FTP client to CHMOD the directory with the proper permissions or your import may fail!').'</p>' . "\n";
	} else if (is_open_perms($upload_directory)) {
		$output .= "\n\n\t" . '<p class="actions">'.plog_tr('Your <strong>plog-content/uploads/</strong> directory has open permissions (0777). If you are not importing images or uploading .zip archives, we recommend that you CHMOD the directory to 0755 to increase security on your Plogger install.').'</p>' . "\n";
	}

	// Grab a list of image files and folders in the uploads so we can clean empty folders
	$content = get_files($config['basedir'].'plog-content/uploads', false, true);
	$files = $content['files'];
	$folders = $content['folders'];

	// Build a list of unique directories from the filenames
	$directories = array();
	foreach ($files as $file) {
		$dirname = dirname($file);
		if (!in_array($dirname, $directories)) {
			$directories[md5($dirname)] = $dirname;
		}
	}

	// clean up empty directories - compare our original $folders array to the new unique $directories array
	foreach ($folders as $folder) {
		if (!in_array($folder, $directories)) {
			kill_dir($folder);
		}
	}

	if (count($files) == 0) {
		$output .= "\n\n\t\t" . '<div class="actions width-700">'.plog_tr('No images found in the <strong>plog-content/uploads/</strong> directory. To mass import pictures into your gallery, simply:').'
			<ul>
				<li><strong>'.plog_tr('Open an FTP connection</strong> to your website').'</li>
				<li>'.plog_tr('Transfer images you wish to publish to the <strong>plog-content/uploads/</strong> directory').'</li>
				<li>'.plog_tr('Optionally, you can create folders within that directory to import in groups').'</li>
			</ul>
		</div>' . "\n";
	}

	// Here we will check which group of pictures we are editing, grouped by directory
	if (!isset($_GET['directory']) && count($directories) > 0) {
		$output .= "\n\n\t\t" . '<div class="actions width-700"><strong>'.plog_tr('Choose a directory you wish to import from').':</strong>';
		$output .= "\n\t\t\t" . '<ul style="list-style-type: none;">';
		foreach ($directories as $dirkey => $group) {
			$output .= "\n\t\t\t\t" . '<li class="margin-5"><a class="folder" href="'.$_SERVER['PHP_SELF'].$query.$sep.'directory='.$dirkey.'">'.basename($group).'</a></li>';
		}
		//$dirkey = md5($upload_directory);
		// $output .= '<li><a class="folder" href="'.$_SERVER['PHP_SELF'].$query.$sep.'directory='.$dirkey.'">All pictures</a></li>';
		$output .= "\n\t\t\t</ul>\n\t\t</div><!-- /actions width-700 -->\n";

	} else {
		// Real_directory is the full path
		// show_directory is what the user sees, it's relative so the directory structure of the server
		// is not exposed
		$show_directory = 'plog-content/uploads';
		if (isset($_GET['directory']) && isset($directories[$_GET['directory']])) {
			$real_directory = $directories[$_GET['directory']];
			$show_directory .= substr($real_directory, strlen($upload_directory));
		} else {
			$real_directory = $upload_directory;
		}

		$files = get_files($real_directory);

		if (count($files) > 0) {
			$percent = (isset($_GET['nojs'])) ? '100%': '0%';
			if (count($files) > 0) {
				$text = (count($files) == 1) ? plog_tr('image') : plog_tr('images');
				$output .= "\n\n\t\t" . '<p class="actions">'.sprintf(plog_tr('You are currently looking at %s within the %s directory.'), '<strong>'.count($files).'</strong> '.$text, '<strong>'.$show_directory.'</strong>').'<br /><br />';
				$output .= "\n\t\t" . sprintf(plog_tr('Creating thumbnails: %s done.'), '<span id="progress" class="strong">'.$percent.'</span>').'</p>' . "\n";
			}
		}

		// Check to make sure album is writable and readable, and issue warning
		if (!is_safe_mode() && (!is_writable($real_directory) || !is_readable($real_directory))) {
			$output .= "\n\n\t\t" . '<p class="errors">'.plog_tr('Warning: This directory does not have the proper permissions settings! You must make this directory writable (CHMOD) using your FTP software, or import may fail.').'</p>';
		}

		$albums = get_albums();
		$queue_func = '';
		$keys = array();
		if ($config['allow_comments']) {
			$comment = plog_tr('Allow Comments').'?';
			$comment_type = 'checkbox" checked="checked';
		} else {
			$comment = '&nbsp;';
			$comment_type = 'hidden';
		}
		sort($files);
		for($i = 0; $i<count($files); $i++) {
			$file_key = md5($files[$i]);
			$keys[] = "'$file_key'";
			$relative_name = substr($files[$i], strlen($upload_directory)+1);
			if ($i == 0)
			$output .= "\n\t\t\t" . '<form id="uploadForm" action="'.$_SERVER["PHP_SELF"].$query.'" method="post" enctype="multipart/form-data">
			<table style="width: 100%;" cellpadding="3" cellspacing="0">
				<tr class="header">
					<th class="table-header-left align-center width-15"><input name="allbox" type="checkbox" onclick="checkAll(document.getElementById(\'uploadForm\'));" checked="checked" /></th>
					<th class="table-header-middle align-left width-175" style="text-indent: 40px;">'.plog_tr('Thumb').'</th>
					<th class="table-header-middle align-left width-200" style="text-align: left;">'.plog_tr('Filename').'</th>
					<th class="table-header-middle align-left">'.plog_tr('Caption &amp; Description (optional)').'</th>
					<th class="table-header-right width-125">'.$comment.'</th>
				</tr>';

			// For each file within upload directory, list checkbox, thumbnail, caption box, description box, allow comments checkbox

			$table_row_color = ($counter%2) ? 'color-1' : 'color-2';
			if (isset($_GET['nojs'])) {
				$thumbpath = generate_thumb($upload_directory.'/'.$relative_name, 'import-'.substr($file_key, 0, 2), THUMB_SMALL);
			} else {
				$thumbpath = $config['gallery_url'].'plog-admin/images/ajax-loader.gif';
			}
			// Start a new table row (alternating colors) and generate XHTML with thumbnail and link to picture view.
			$output .= "\n\t\t\t\t" . '<tr class="'.$table_row_color.'">
					<td class="align-center width-15"><p class="margin-5"><input type="checkbox" name="selected[]" value="'.$file_key.'" checked="checked" /></p></td>
					<td class="align-left width-175"><div class="img-shadow" id="pic_'.$file_key.'"><img src="'.$thumbpath.'" alt="thumbnail" /></div></td>
					<td class="align-left width-200 vertical-top">'.basename($files[$i]).'</td>
					<td class="align-left vertical-top">
						<input type="text" size="60" style="width: 95%;" name="captions['.$file_key.']" /><br />
						<textarea name="descriptions['.$file_key.']" rows="4" cols="60" style="width: 95%;"></textarea>
					</td>
					<td class="align-center width-125"><input type="'.$comment_type.'" name="allow_comments[]" value="'.$file_key.'" /></td>
				</tr>';
			$counter++;
		}

		if (count($files) != 0) {
			$output .= "\n\t\t\t\t" . '<tr class="footer">
					<td class="align-left invert-selection" colspan="5"><a href="#" onclick="checkToggle(document.getElementById(\'uploadForm\')); return false; ">'.plog_tr('Toggle Checkbox Selection').'</a></td>
				</tr>' . "\n";
			$output .= "\t\t\t" . '</table>' . "\n";

			// Here we can preselect some default options based on the structure of the import directory
			// If pictures are within one directory, simply place the name of the album within the
			// create new album selector and allow user to pick collection.
			// If two levels deep, preselect appropriate existing album and collection
			// or place album name in new box

			// Break up directory name into parts
			$directory_parts = explode('/', $show_directory);

			if (isset($_REQUEST['collection_name']) && isset($_REQUEST['album_name'])) {
				$collection_name = $_REQUEST['collection_name'];
				$album_name = $_REQUEST['album_name'];
			} else {
				$collection_name = @$directory_parts[2];
				$album_name = @$directory_parts[3];
			}

			// Check if album exists
			if (is_null($album_name)) // file is only one level deep, assume folder name is album name
			$sql = "SELECT id FROM ".PLOGGER_TABLE_PREFIX."albums WHERE name = '".mysql_real_escape_string($collection_name)."'";
			else
			$sql = "SELECT id FROM ".PLOGGER_TABLE_PREFIX."albums WHERE name = '".mysql_real_escape_string($album_name)."'";

			$result = run_query($sql);
			$row = mysql_fetch_assoc($result);
			$new_album_name = '';

			if(!isset($row['id'])) { // Album doesn't exist, place in new album box
				$existing = '';
				$new_album = 'checked="checked"';
				if (is_null($album_name)) {
					$new_album_name = $collection_name;
				} else {
					$new_album_name = $album_name;
				}
			} else {
				$existing = 'checked="checked"';
				$new_album = '';
			}

			$output .= "\n\t" . '<h1>'.plog_tr('Destination:').'</h1>

		<div class="import">
			<p class="no-margin-top">
				<input accesskey="a" type="radio" name="destination_radio" id="destination_radio" value="existing" '.$existing.' style="margin-bottom: -1px;" />
				<label for="destination_radio" class="strong" style="display: inline;">'.plog_tr('Existing <em>A</em>lbum').'</label><br /><br />
				'.generate_albums_menu($albums, 'single', $row['id']).'
			</p>

			<h3 class="no-margin-top" style="text-indent: 10px;">'.plog_tr('-- OR --').'</h3>

			<p class="no-margin-top">
				<input accesskey="b" onclick="var k=document.getElementsByName(\'new_album_name\');k[0].focus()" type="radio" name="destination_radio" value="new" '.$new_album.' style="margin-bottom: -1px;" />
				<label for="destination_radio" class="strong" style="display: inline;">'.plog_tr('Create a New Al<em>b</em>um').'</label>
				<label for="new_album_name">'.plog_tr('New Album Name:').'</label>
				<input type="text" name="new_album_name" id="new_album_name" value="'.ucfirst($new_album_name).'" onclick="var k=document.getElementsByName(\'destination_radio\');k[1].checked=true;" />
				<label for="collections_menu">'.plog_tr('In collection:').'</label>
				'.generate_collections_menu().'
			</p>

			<p class="no-margin-bottom"><input class="submit" type="submit" name="upload" value="'.plog_tr('Import').'" /></p>
		</div><!-- /import -->';

			$output .= "\n\t</form>";
			if (!isset($_GET['nojs'])) {
				$key_arr = join(",\n\t\t\t", $keys);

				$output .= "\n\n\t" . '<script type="text/javascript">';
				$output .= "\n\t\t" . 'var importThumbs=[' . "\n\t\t\t";
				$output .= $key_arr;
				$output .= "\n\t\t" . '];';
				$output .="\n\t\t" . 'requestImportThumb();' . "\n\t</script>\n";
			}
		}
	}
}

$output_error = "\n\t" . '<h1>'.plog_tr('Import').'</h1>

	<p class="actions width-700">'.sprintf(plog_tr('Before you can begin importing images to your gallery, you must create at least <strong>one collection</strong> AND <strong>one album</strong> within that collection. Move over to the <a title="Manage" style="font-weight: bold;" href="%s">Manage</a> tab to begin creating your organizational structure'), 'plog-manage.php').'</p>';

$num_albums = count_albums();

if ($num_albums > 0)
	display($output, 'import');
else
	display($output_error, 'import');

?>