<?php
/* Code by Mike Johnson -- mike@solanosystems.com October 23rd, 2004.
 This is the main administrative interface code. To change the look of the interface, change /plog-admin/css/admin.css.
 The initial tab is UPLOAD function. */

// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');
require(PLOGGER_DIR.'plog-admin/plog-admin.php');

function generate_albums_menu($albums) {
	$albums_menu = isset($_REQUEST['albums_menu']) ? $_REQUEST['albums_menu'] : '';
	$new_album_name = isset($_REQUEST['new_album_name']) ? $_REQUEST['new_album_name'] : '';
	$output = '<select tabindex="50" style="width: 80%;" name="albums_menu" onclick="var k=document.getElementsByName(\'destination_radio\');k[0].checked=true;">';
	foreach($albums as $album_id => $album) {

		if ($albums_menu == $album_id || $new_album_name == $album['album_name']) {
			$selected = ' selected="selected"';
		} else {
			$selected = '';
		}

		$output .= "\n\t\t\t\t\t\t" . '<option value="'.$album_id.'"'.$selected.'>'.SmartStripSlashes($album['collection_name']).': '.SmartStripSlashes($album['album_name']).'</option>';
	}
	$output .= "\n\t\t\t\t\t</select>";

	return $output;
}

function generate_collections_menu() {
	$collections = get_collections();
	$output = '<select class="no-margin-top" tabindex="80" style="width: 80%;" name="collections_menu" id="collections_menu">';
	foreach($collections as $collection) {

		$output .= "\n\t\t\t\t\t\t" . '<option value="'.$collection['id'].'">'.SmartStripSlashes($collection['name']).'</option>';
	}
	$output .= "\n\t\t\t\t\t</select>";

	return $output;
}

$output = "\n\t" . '<h1>'.plog_tr('Upload Images').'</h1>' . "\n";

// Check if update has been clicked, handle erroneous conditions, or upload
if (isset($_REQUEST['upload'])) {
	foreach($_REQUEST as $key => $val) $_REQUEST[$key] = stripslashes($val);

	$pi = pathinfo($_FILES['userfile']['name']);

	if ($_FILES['userfile']['name'] == '') {
		$output .= "\n\t" . '<p class="errors">'.plog_tr('No filename specified').'!</p>' . "\n";
	} else if (strtolower($pi['extension']) == 'zip') {
		// Let's decompress the zip file into the 'plog-content/uploads/' folder and then redirect the user to plog-import.php
		include(PLOGGER_DIR.'plog-includes/lib/pclzip-2-4/pclzip.lib.php');
		// Zip file to extract
		$archive = new PclZip($_FILES['userfile']['tmp_name']);

		// Create a temporary folder in 'plog-content/uploads/' based on the .zip file name
		$zipname = strtolower(sanitize_filename(substr($_FILES['userfile']['name'], 0, -4)));
		$zipdir = $config['basedir'].'plog-content/uploads/'.$zipname;
		$zipdirkey = md5($zipdir);
		$zipresult = makeDirs($zipdir);

		if (is_safe_mode()) {
			chmod_ftp($zipdir, 0777);
		}

		// Extract to 'plog-content/uploads/' folder
		$results = $archive->extract(PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_PATH, $zipdir);
		if (is_safe_mode()) {
			chmod_ftp($zipdir);
		}

		if ($results == 0) {
			// Failed
			$output .= "\n\t" . '<p class="errors">'.plog_tr('Error').': '.$archive->errorInfo(true).'</p>' . "\n";
		} else {
			// Unzip succeeded - doesn't necessarily mean that saving the images succeeded
			$errors = array();

			foreach ($results as $r) {
				if ($r['status'] != 'ok') {
					$errors[] = $r;
				}
			}

			if (empty($errors)) {
				// Let's redirect to the import interface.
				header('location: plog-import.php?directory='.$zipdirkey);
				exit;
			} else {
				$output .= "\n\t" . '<p class="errors">'.plog_tr('There were some problems importing the files').':<br /><br />' . "\n";

				foreach ($errors as $e) {
					$output .= $e['stored_filename'].': '.$e['status'].'<br />';
				}

				$output .= '<br />' .
				sprintf(plog_tr('You can proceed to the <a href="%s">Import</a> section to view any files that were successfully uploaded'), 'plog-import.php').'.</p>' . "\n";
			}
		}

	} else if (!is_allowed_extension($pi['extension'])) {
		$output .= "\n\t" . '<p class="errors">'.plog_tr('Plogger cannot handle this type of file').'.</p>' . "\n";
	} else if ($_FILES['userfile']['error'] == 1) {
		$output .= "\n\t" . '<p class="errors">'.plog_tr('File exceeded upload filesize limit').'!</p>' . "\n";
	} else if ($_FILES['userfile']['size'] == 0) {
		$output .= "\n\t" . '<p class="errors">'.plog_tr('File does not exist').'!</p>' . "\n";
	} else if (!isset($_REQUEST['destination_radio'])) {
		$output .= "\n\t" . '<p class="errors">'.plog_tr('No destination album specified').'!</p>' . "\n";
	} else {
		if ($_REQUEST['destination_radio'] == 'new' && $_REQUEST['new_album_name'] == ''){
			$output .= "\n\t" . '<p class="errors">'.plog_tr('New album name not specified').'!</p>' . "\n";
		} else {
			if ($_REQUEST['destination_radio'] == 'new') {
				// Create the new album
				$result = add_album(mysql_real_escape_string($_REQUEST['new_album_name']), NULL, $_REQUEST['collections_menu']);
				if (!$result['errors']) {
					// No errors, add uploaded image to new album
					$album_id = $result['id'];
				} else {
					// Errors exist, let's find out what they are
					if (isset($result['output']) && $result['output'] == 'existing' && isset($result['id'])) {
						// Album already exists so try insert images into the existing album
						// and alert the user that their "new" album is already existing
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
				$album_id = $_REQUEST['albums_menu'];
			}

			if ($album_id) {
				$result = add_picture($album_id, $_FILES['userfile']['tmp_name'], $_FILES['userfile']['name'], $_REQUEST['caption'], $_REQUEST['description']);
				if (!$result['errors']) {
					// Added uploaded image successfully
					$output .= "\n\t" . '<p class="success">'.$result['output'].'</p>' . "\n";
				} else {
					// Errors adding the image, show the returned error
					$output .= "\n\t" . '<p class="errors">'.$result['errors'].'</p>' . "\n";
				}
			}

		}
	}
}

$output .= "\n\t" . '<form id="uploadForm" action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">
	<table class="cssbox-upload" cellspacing="0" cellpadding="0">
		<tr style="margin: 0;">
			<th class="cssbox-upload-head-blue"><h2>'.plog_tr('Choose an Image or ZIP Archive').'</h2></th>
			<th></th>
			<th class="cssbox-upload-head-green"><h2>'.plog_tr('Choose a Destination Album').'</h2></th>
		</tr>
		<tr>
			<td class="cssbox-upload-body">
				<div class="no-margin-top no-margin-bottom">
					<label class="no-margin-top" accesskey="n" for="userfile">'.sprintf(plog_tr('File<em>n</em>ame (%s limit)'), ini_get('upload_max_filesize')).':</label>
					<input class="no-margin-top" tabindex="10" id="userfile" name="userfile" value="Vali fail" type="file" onchange="checkArchive(this)" />';

if (!is_writable(PLOGGER_DIR.'plog-content/uploads/')) {
	$output .= "\n\t\t\t\t\t" . '<p class="actions" id="zip-alert" style="display: none;">'.sprintf(plog_tr('Please make sure the %s directory is writable before uploading a %s file'), '<strong>plog-content/uploads/</strong>', '.zip').'</p>';
}

$output .= "\n\t\t\t\t\t" . '<label accesskey="c" for="caption">'.plog_tr('Picture <em>C</em>aption (optional)').':</label>
					<input class="no-margin-top" tabindex="20" name="caption" id="caption" style="width: 90%;" />
					<label accesskey="d" for="description">'.plog_tr('<em>D</em>escription (optional)').':</label>
					<textarea class="no-margin-top" tabindex="30" name="description" id="description" style="width: 90%;" cols="43" rows="6"></textarea>
				</div><!-- /no-margin-top no-margin-bottom -->
			</td>';

$albums = get_albums();

$output .= "\n\t\t\t" . '<td style="width: 2%;">&nbsp;</td>
			<td class="cssbox-upload-body">
				<div class="no-margin-bottom">
					<input tabindex="40" onclick="var k=document.getElementsByName(\'albums_menu\');k[0].focus();" type="radio" name="destination_radio" id="destination_radio" accesskey="a" value="existing" checked="checked" />
					<label for="destination_radio" style="display: inline;">'.plog_tr('Existing <em>A</em>lbum').'</label>
					'.generate_albums_menu($albums).'
				</div><!-- /no-margin-bottom -->
				<h3 style="text-indent: 10px; margin-bottom: 15px;">'.plog_tr('-- OR --').'</h3>
				<div>
					<input tabindex="60" onclick="var k=document.getElementsByName(\'new_album_name\');k[0].focus();" type="radio" name="destination_radio" accesskey="b" value="new" />
					<label for="new_album_name" style="display: inline;">'.plog_tr('Create a New Al<em>b</em>um').'</label>
					<label class="no-margin-bottom" for="new_album_name" style="font-weight: normal;">'.plog_tr('New Album Name').':</label>
					<input class="no-margin-top" tabindex="70" style="width: 79%;" onclick="var k=document.getElementsByName(\'destination_radio\');k[1].checked=true;" type="text" id="new_album_name" name="new_album_name" />
					<label class="no-margin-bottom" for="collections_menu" style="font-weight: normal;">'.plog_tr('In Collection').':</label>
					'.generate_collections_menu().'
				</div>
				<p class="align-left no-margin-top no-margin-bottom" style="text-indent: 5px;"><input class="submit" type="submit" name="upload" value="'.plog_tr('Upload').'" /></p>
			</td>
		</tr>
	</table>
	</form>'."\n";

$output_error = "\n\t" . '<h1>'.plog_tr('Upload Images').'</h1>

	<p class="actions">'.sprintf(plog_tr('Before you can begin uploading images to your gallery, you must create at least <strong>one collection</strong> AND <strong>one album</strong> within that collection. Move over to the <a href="%s">Manage</a> tab to begin creating your gallery structure.'), 'plog-manage.php').'</p>';

require_once(PLOGGER_DIR.'plog-admin/includes/install-functions.php');

if (gd_missing()) {
	$output_error = "\n\t" . '<h1>'.plog_tr('Upload Images').'</h1>

	<p class="errors">'.plog_tr('PHP GD extension is not installed, it is required to upload images.').'</p>';
	display($output_error, 'upload');
} else {
	$num_albums = count_albums();
	if ($num_albums > 0)
	display($output, 'upload');
	else
	display($output_error, 'upload');
}
?>