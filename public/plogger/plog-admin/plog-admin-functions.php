<?php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	// ignorance is bliss
	exit();
}

function get_files($directory, $get_all_files = false, $get_folders = false, $relative_path = false) {
	global $config;
	$sep = (substr($directory, -1) == '/') ? '': '/';
	// Try to open the directory
	if($dir = opendir($directory)) {
		// Create an array for all files found
		$tmp = array();
		// Create an array for all folders found (if set)
		$tmp_folders = array();

		// Add the files
		while($file = readdir($dir)) {
			// Make sure the file exists
			if($file != '.' && $file != '..') {
				if (!$get_folders) {
					if ($file[0] == '.') {
						continue;
					}
				}
				// If it's a directory, list all files within it
				if (is_dir($directory.$sep.$file)) {
					$tmp2 = get_files($directory.$sep.$file, $get_all_files, $get_folders, $relative_path);
					if (is_array($tmp2)) {
						if (!isset($tmp2['files'])) {
							$tmp = array_merge($tmp, $tmp2);
						} else {
							$tmp = array_merge($tmp, $tmp2['files']);
							$tmp_folders = array_merge($tmp_folders, $tmp2['folders']);
						}
					}
					if ($get_folders) {
						if (!$relative_path) {
							$tmp_folders[] = $directory.$sep.$file;
						} else {
							$tmp_folders[] = str_replace($relative_path, '', $directory.$sep.$file);
						}
					}
				} else if (is_readable($directory.$sep.$file)) {
					$filename = basename(stripslashes($file));
					$pi = pathinfo($file);
					if (is_allowed_extension($pi['extension']) || $get_all_files) {
						if (!$relative_path) {
							$tmp[] = $directory.$sep.$file;
						} else {
							$tmp[] = str_replace($relative_path, '', $directory.$sep.$file);
						}
					}
				}
			}
		}
		// Finish off the function
		closedir($dir);
		sort($tmp);
		if ($get_folders) {
			$return = array();
			// Reverse the order of folders so subfolders come first 
			krsort($tmp_folders);
			$return['files'] = $tmp;
			$return['folders'] = $tmp_folders;
			return $return;
		}
		return $tmp;
	}
}

function move_this($item, $destination) {
	// If safe_mode enabled, open the permissions first
	if (is_safe_mode()) {
		$old_parent_path = dirname($item).'/';
		$new_parent_path = dirname($destination).'/';
		chmod_ftp($old_parent_path, 0777);
		chmod_ftp($new_parent_path, 0777);
	}
	$move = @rename($item, $destination);
	// If safe_mode enabled, close the permissions back down to the default
	if (is_safe_mode()) {
		chmod_ftp($old_parent_path);
		chmod_ftp($new_parent_path);
	}
	if (!$move) {
		return false;
	}
	return true;
}

function kill_dir($path) {
// Great removal function originally named advancedRmdir() by kisgabo94 at freemail dot hu
	// if the path exists, attempt to delete it, else we don't need to do anything
	if (isset($path) && file_exists($path)) {
		$origipath = $path;
		$handler = opendir($path);
		while (true) {
			$item = readdir($handler);
			if ($item == '.' or $item == '..') {
				continue;
			} elseif (gettype($item) == 'boolean') {
				closedir($handler);
				// If safe_mode enabled, open the permissions first
				if (is_safe_mode() && !is_writable(dirname($path).'/')) {
					$parent_path = dirname($path).'/';
					chmod_ftp($parent_path, 0777);
				}
				$remove = @rmdir($path);
				// If safe_mode enabled, close the permissions back down to the default
				if (is_safe_mode()) {
					chmod_ftp($parent_path);
				}
				if (!$remove) {
					return false;
				}
				if ($path == $origipath) {
					break;
				}
				$path = substr($path, 0, strrpos($path, '/'));
				$handler = opendir($path);
			} elseif (is_dir($path.'/'.$item)) {
				closedir($handler);
				$path = $path.'/'.$item;
				$handler = opendir($path);
			} else {
				// If safe_mode enabled, open the permissions first
				if (is_safe_mode() && !is_writable($path)) {
					chmod_ftp($path.'/', 0777);
				}
				@unlink($path.'/'.$item);
			}
		}
	}
	return true;
}

function kill_file($file) {
	// if the path exists, attempt to delete it, else we don't need to do anything
	if (isset($file) && file_exists($file)) {
		// Check if it's an uploaded file
		$uploaded = is_uploaded_file($file);
		// If safe_mode enabled, open the permissions first
		if (is_safe_mode() && !$uploaded) {
			$parent_path = dirname($file).'/';
			chmod_ftp($parent_path, 0777);
		}
		$remove = @unlink($file);
		// If safe_mode enabled, close the permissions back down to the default
		if (is_safe_mode() && !$uploaded) {
			chmod_ftp($parent_path);
		}
		if (!$remove) {
			return false;
		}
	}
	return true;
}

function is_win() {
	if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
		return true;
	}
	return false;
}

function is_open_perms($file) {
	if (!is_win()) {
		clearstatcache();
		$perm = substr(decoct(fileperms($file)),2);
		return ($perm == '0777');
	}
	return false;
}

function generate_pagination_view_menu() {
	$url_query = '?';
	$url_parts = parse_url($_SERVER['REQUEST_URI']);
	if (isset($url_parts['query'])) {
		// If entries_per_page is already present in URL, remove it
		if (strpos($url_parts['query'], 'entries_per_page') !== false || strpos($url_parts['query'], 'plog_page') !== false) {
			parse_str($url_parts['query'], $query_parts);
			foreach ($query_parts as $qkey => $qval) {
				if ($qkey != 'entries_per_page' && $qkey != 'plog_page') {
					$url_query .= $qkey.'='.$qval.'&amp;';
				}
			}
		} else {
			$url_query .= str_replace('&', '&amp;', $url_parts['query']).'&amp;';
		}
	}

	$java = 'document.location.href=\''.$url_parts['path'].$url_query.'entries_per_page=\'+this.options[this.selectedIndex].value';

	$possible_values = array('1'=>1, '5'=>5, '10'=>10, '20'=>20, '50'=>50, '100'=>100, '250'=>250, '500'=>500);
	$output= "\n\t\t\t" . '<label accesskey="e" for="entries_per_page">'.plog_tr('<em>E</em>ntries per page').'</label>
			<select class="entries-page" onchange="'.$java.'" name="entries_per_page" id="entries_per_page">';
	foreach ($possible_values as $key => $value) {
		if ($_SESSION['entries_per_page'] == $key) {
			$output .= "\n\t\t\t\t" . '<option value="'.$value.'" selected="selected">'.$key.'</option>';
		} else {
			$output .= "\n\t\t\t\t" . '<option value="'.$value.'">'.$key.'</option>';
		}
	}
	$output.= "\n\t\t\t" . '</select>';
	$output.= "\n\t\t\t" . '<input id="pagination-go" class="submit" type="submit" value="'.plog_tr('Go').'" />';
	$output.= "\n\t\t\t<script type=\"text/javascript\">toggle('pagination-go');</script>";
	return $output;
}

function add_picture($album_id, $tmpname, $filename, $caption, $desc, $allow_comm = 1) {
	global $config;

	$filename_parts = explode('.', strrev($filename), 2);
	$filename_base = strrev($filename_parts[1]);
	$filename_ext = strtolower(strrev($filename_parts[0]));

	$ext_array = array('jpg', 'jpeg', 'gif', 'png', 'bmp');

	$result = array(
		'output' => '',
		'errors' => '',
		'picture_id' => false,
	);

	$i = 0;

	$unique_filename_base = strtolower(sanitize_filename(SmartStripSlashes($filename_base), true));

	// Now get the name of the collection
	$sql = "SELECT c.path AS collection_path, c.id AS collection_id,
			a.path AS album_path, a.id AS album_id
			FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."collections c
			WHERE c.id = a.parent_id AND a.id = '$album_id'";

	$sql_result = run_query($sql);
	$albumdata = mysql_fetch_assoc($sql_result);

	// This shouldn't happen in normal cases
	if (empty($albumdata)) {
		$result['errors'] .= plog_tr('No such album!');
		return $result;
	}

	$dest_album_name = SmartStripSlashes($albumdata['album_path']);
	$dest_collection_name = SmartStripSlashes($albumdata['collection_path']);

	$create_path = $dest_collection_name.'/'.$dest_album_name;

	foreach ($ext_array as $ext) {
		while (is_file($config['basedir'].'plog-content/images/'.$create_path.'/'.$unique_filename_base.'.'.$ext)) {
			$unique_filename_base = SmartStripSlashes($filename_base).'-'.++$i;
		}
	}

	$final_filename = sanitize_filename($unique_filename_base).'.'.$filename_ext;

	// Final fully qualified filename
	$final_fqfn = $config['basedir'].'plog-content/images/'.$create_path.'/'.$final_filename;

	if (!makeDirs($config['basedir'].'plog-content/images/'.$create_path)) {
		$result['errors'] .= sprintf(plog_tr('Could not create directory %s!'), '<strong>'.$create_path.'</strong>');
		return $result;
	}

	if (is_uploaded_file($tmpname)) {
		// If safe_mode enabled, open the permissions if the destination path
		if (is_safe_mode()) {
			$parent_path = $config['basedir'].'plog-content/images/'.$create_path;
			chmod_ftp($parent_path, 0777);
		}
		if (!move_uploaded_file($tmpname, $final_fqfn)) {
			$result['errors'] .= sprintf(plog_tr('Could not move uploaded file: %s to %s'), '<strong>'.$tmpname.'</strong>', '<strong>'.$final_fqfn.'</strong>');
		}
		// If safe_mode enabled, close the permissions back down to the default
		if (is_safe_mode()) {
			chmod_ftp($parent_path);
		}
	} else {
		if (!move_this($tmpname, $final_fqfn)) {
			$result['errors'] .= sprintf(plog_tr('Could not move file: %s to %s'), '<strong>'.$tmpname.'</strong>', '<strong>'.$final_fqfn.'</strong>');
		}
	}

	if (empty($result['errors'])) {
		if (is_file($tmpname)) {
			kill_file($tmpname);
		}
		$res = @chmod($final_fqfn, PLOGGER_CHMOD_FILE);

		// Get the EXIF data.
		require_once(PLOGGER_DIR.'plog-includes/lib/exifer1_7/exif.php');
		$exif_raw = read_exif_data_raw($final_fqfn, false);
		$exif = array();

		$exif['date_taken'] = (isset($exif_raw['SubIFD']['DateTimeOriginal'])) ? trim($exif_raw['SubIFD']['DateTimeOriginal']) : '';
		$exif['camera'] = (isset($exif_raw['IFD0']['Make']) && isset($exif_raw['IFD0']['Model'])) ? trim($exif_raw['IFD0']['Make']).' '.trim($exif_raw['IFD0']['Model']) : '';
		$exif['shutter_speed'] = (isset($exif_raw['SubIFD']['ExposureTime'])) ? $exif_raw['SubIFD']['ExposureTime'] : '';
		$exif['focal_length'] = (isset($exif_raw['SubIFD']['FocalLength'])) ? $exif_raw['SubIFD']['FocalLength'] : '';
		$exif['flash'] = (isset($exif_raw['SubIFD']['Flash'])) ? $exif_raw['SubIFD']['Flash'] : '';
		$exif['aperture'] = (isset($exif_raw['SubIFD']['FNumber'])) ? $exif_raw['SubIFD']['FNumber'] : '';
		$exif['iso'] = (isset($exif_raw['SubIFD']['ISOSpeedRatings'])) ? $exif_raw['SubIFD']['ISOSpeedRatings'] : '';

		$picture_path = $create_path.'/'.$final_filename;

		$query = "INSERT INTO `".PLOGGER_TABLE_PREFIX."pictures`
			(`parent_collection`,
			`parent_album`,
			`path`,
			`date_modified`,
			`date_submitted`,
			`allow_comments`,
			`EXIF_date_taken`,
			`EXIF_camera`,
			`EXIF_shutterspeed`,
			`EXIF_focallength`,
			`EXIF_flash`,
			`EXIF_aperture`,
			`EXIF_iso`,
			`caption`,
			`description`)
			VALUES
				('".$albumdata['collection_id']."',
				'".$albumdata['album_id']."',
				'".mysql_real_escape_string($picture_path)."',
				NOW(),
				NOW(),
				".intval($allow_comm).",
				'".mysql_real_escape_string($exif['date_taken'])."',
				'".mysql_real_escape_string($exif['camera'])."',
				'".mysql_real_escape_string($exif['shutter_speed'])."',
				'".mysql_real_escape_string($exif['focal_length'])."',
				'".mysql_real_escape_string($exif['flash'])."',
				'".mysql_real_escape_string($exif['aperture'])."',
				'".mysql_real_escape_string($exif['iso'])."',
				'".mysql_real_escape_string($caption)."',
				'".mysql_real_escape_string($desc)."')";

		$sql_result = run_query($query);

		$result['output'] .= sprintf(plog_tr('Your image %s was uploaded successfully.'), '<strong>'.$filename.'</strong>');
		$result['picture_id'] = mysql_insert_id();

		// Let's generate the thumbnail and the large thumbnail right away.
		// This way, the user won't see any latency from the thumbnail generation
		// when viewing the gallery for the first time
		// This also helps with the image pre-loading problem introduced
		// by a javascript slideshow.

		$thumbpath = generate_thumb($picture_path, $result['picture_id'], THUMB_SMALL);
		//$thumbpath = generate_thumb($picture_path, $result['picture_id'],THUMB_LARGE);
	}

	return $result;
}

function update_picture($id, $caption, $allow_comments, $description) {
	$id = intval($id);
	$caption = mysql_real_escape_string($caption);
	$description = mysql_real_escape_string($description);
	$allow_comments = intval($allow_comments);
	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."pictures SET
			caption = '$caption',
			description = '$description',
			allow_comments = '$allow_comments'
		WHERE id='$id'";
	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected picture.'));
	} else {
		return array('errors' => mysql_error());
	}
}

function update_picture_field($picture_id, $field, $value) {
	$fields = array('caption', 'description');
	if (!in_array($field, $fields)) {
		return array('errors' => plog_tr('Invalid action'));
	}

	$errors = $output = '';

	$picture_id = intval($picture_id);
	$value = mysql_real_escape_string(trim($value));

	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."pictures SET $field = '$value' WHERE id='$picture_id'";

	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected picture.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected picture.'));
	}

}

function move_picture($pic_id, $to_album) {
	global $config, $thumbnail_config;
	// We need the parent_id from the album we're changing to
	$to_album = intval($to_album);
	$pic_id = intval($pic_id);

	$query = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."albums WHERE `id` = '".$to_album."'";
	$result = run_query($query);
	$row = mysql_fetch_assoc($result);

	if (!is_array($row)) {
		return array('errors' => sprintf(plog_tr('There is no album with id %s.'), '<strong>'.$to_album.'</strong>'));
	}

	$new_collection = $row['parent_id'];

	// Move picture to new location
	// We need to query to get collection names and album names to find new directory path

	$picture = get_picture_by_id($pic_id);
	// If attempting to move within the same album, abort
	if ($picture['parent_album'] == $to_album) {
		return;
	}
	$album = get_album_by_id($to_album);

	$filename = SmartStripSlashes(basename($picture['path']));
	$target_path = SmartStripSlashes($album['collection_path']).'/'.SmartStripSlashes($album['album_path']);

	$filename_parts = explode('.', strrev($filename), 2);
	$filename_base = strrev($filename_parts[1]);
	$filename_ext = strrev($filename_parts[0]);
	$unique_filename_base = strtolower(SmartStripSlashes($filename_base));

	$i = 0;
	while ($to_album != $picture['parent_album'] && is_file($config['basedir'].'plog-content/images/'.$target_path.'/'.$unique_filename_base.'.'.$filename_ext)) {
		$unique_filename_base = $filename_base.'('.++$i.')';
	}

	// Final fully qualified file name
	$picture_path = $target_path.'/'.sanitize_filename($unique_filename_base).'.'.$filename_ext;
	$final_fqfn = $config['basedir'].'plog-content/images/'.$picture_path;

	$rename = move_this($config['basedir'].'plog-content/images/'.$picture['path'], $final_fqfn);
	@chmod($final_fqfn, PLOGGER_CHMOD_FILE);

	// Delete thumbnails
	foreach($thumbnail_config as $tval) {
		$thumbpath = $config['basedir'].'plog-content/thumbs/'.dirname($picture['path']).'/'.$tval['type'].'/'.$picture['id'].'-'.$filename;
		if (file_exists($thumbpath)) {
			kill_file($thumbpath);
		}
	}

	if (!$rename) {
		return array('errors' => sprintf(plog_tr('Could not move file: %s to %s'), '<strong>'.$picture['path'].'</strong>', '<strong>'.$final_fqfn.'</strong>'));
	}

	// Check if collection thumbnail = picture moved to different collection and set to default if so
	if ($picture['parent_collection'] != $new_collection) {
		$collection = get_collection_by_id($picture['parent_collection']);
		if ($collection['thumbnail_id'] == $picture['id']) {
			$query = "UPDATE ".PLOGGER_TABLE_PREFIX."collections SET `thumbnail_id`='0' WHERE id='".$collection['id']."'";
			run_query($query);
		}
	}
	// Check if album thumbnail = moved picture and set to default if so
	$album = get_album_by_id($picture['parent_album']);
	if ($album['thumbnail_id'] == $picture['id']) {
		$query = "UPDATE ".PLOGGER_TABLE_PREFIX."albums SET `thumbnail_id`='0' WHERE id='".$album['id']."'";
		run_query($query);
	}

	// Update database
	$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."pictures SET
			path = '".mysql_real_escape_string($picture_path)."',
			parent_album = '".$to_album."',
			parent_collection = '".$new_collection."'
		WHERE id = '".$pic_id."'";
	if (!mysql_query($sql)) {
		return array('errors' => mysql_error());
	}
	return array('output' => plog_tr('Success'));
}

function delete_picture($del_id) {
	global $config, $thumbnail_config;
	$del_id = intval($del_id);
	$picture = get_picture_by_id($del_id);
	if ($picture) {
		// Check if collection thumbnail = deleted picture and set to default if so
		$collection = get_collection_by_id($picture['parent_collection']);
		if ($collection['thumbnail_id'] == $picture['id']) {
			$query = "UPDATE ".PLOGGER_TABLE_PREFIX."collections SET `thumbnail_id`='0' WHERE id='".$collection['id']."'";
			run_query($query);
		}
		// Check if album thumbnail = deleted picture and set to default if so
		$album = get_album_by_id($picture['parent_album']);
		if ($album['thumbnail_id'] == $picture['id']) {
			$query = "UPDATE ".PLOGGER_TABLE_PREFIX."albums SET `thumbnail_id`='0' WHERE id='".$album['id']."'";
			run_query($query);
		}

		$query = "DELETE FROM ".PLOGGER_TABLE_PREFIX."pictures WHERE `id`= '".$picture['id']."'";
		run_query($query);

		// Delete all comments for the picture
		$query = "DELETE FROM ".PLOGGER_TABLE_PREFIX."comments WHERE `parent_id`= '".$picture['id']."'";
		run_query($query);

		// Make sure that the file is actually located inside our 'plog-content/images/' directory
		$full_path = $config['basedir'].'plog-content/images/'.SmartStripSlashes($picture['path']);
		// Also check whether this image is in the correct folder
		$relative_path = substr($full_path, 0, strlen($config['basedir']));
		$basename = SmartStripSlashes(basename($picture['path']));
		if ($relative_path == $config['basedir']) {
			foreach($thumbnail_config as $tval) {
				$thumbpath = $config['basedir'].'plog-content/thumbs/'.dirname($picture['path']).'/'.$tval['type'].'/'.$picture['id'].'-'.$basename;
				if (file_exists($thumbpath)) {
					kill_file($thumbpath);
				}
			}
			if (is_file($full_path)) {
				if (!kill_file($full_path)) {
					$errors = plog_tr('Could not physically delete file from disk!');
				}
			}
		} else {
			$errors = plog_tr('Picture has invalid path, ignoring delete request.');
		}
	} else {
		$errors =  sprintf(plog_tr('There is no picture with id %s.'), '<strong>'.$del_id.'</strong>');
	}
	if (isset($errors)) {
		return array('errors' => $errors);
	}
	return true;
}

function add_collection($collection_name, $description) {
	global $config;
	$output = $errors = '';
	$id = 0;
	$collection_name = trim(SmartStripSlashes($collection_name));
	if (empty($collection_name)) {
		return array('errors' => plog_tr('Please enter a valid name for the collection.'));
	}

	$collection_folder = strtolower(sanitize_filename($collection_name));

	// First try to create the directory, and only if that succeeds, then insert a new
	// row into collections table, otherwise the collection will not be usable anyway
	$create_path = $config['basedir'].'plog-content/images/'.$collection_folder;

	// Do not allow collections with duplicate names, otherwise mod_rewritten links will start
	// to behave weird.
	if (is_dir($create_path)) {
		// If there is already a directory, check to see if it's in the database
		$collection_data = get_collection_by_name($collection_name);
		if ($collection_data) {
			// It's in the database, so throw duplicate collection error
			return array('errors' => sprintf(plog_tr('New collection could not be created, because there is already one named %s!'), '<strong>'.$collection_name.'</strong>'));
		} else {
			// It's not in the database so attempt to delete the directory
			if (!kill_dir($create_path)) {
				// Could not delete the directory, so prompt the user to delete it manually
				return array('errors' => sprintf(plog_tr('Collection directory %s exists, but no collection exists in the database. Attempt to delete automatically failed. Please delete folder via FTP manually and try again.'), '<strong>'.$create_path.'</strong>'));
			}
		}
	}

	// Create directory
	if (!makeDirs($create_path)) {
		$errors .= sprintf(plog_tr('Could not create directory %s!'), '<strong>'.$create_path.'</strong>');
	} else {
		$sql_name = mysql_real_escape_string($collection_name);
		$description = mysql_real_escape_string($description);
		$collection_folder = mysql_real_escape_string($collection_folder);
		$query = "INSERT INTO ".PLOGGER_TABLE_PREFIX."collections (`name`,`description`,`path`) VALUES ('$sql_name', '$description', '$collection_folder')";
		$result = run_query($query);
		$id = mysql_insert_id();

		$output .= sprintf(plog_tr('You have successfully created the collection %s.'), '<strong>'.$collection_name.'</strong>');
	}

	// Caller can check the value of id, if it is zero, then collection creation failed
	// errors and output are separate, because this way the caller can format the return value
	// as it needs
	$result = array(
		'output' => $output,
		'errors' => $errors,
		'id' => $id,
	);
	return $result;

}

function update_collection($collection_id, $name, $description, $thumbnail_id = 0) {
	global $config;

	$errors = $output = '';

	$name = trim(SmartStripSlashes($name));
	if (empty($name)) {
		return array('errors' => plog_tr('Please enter a valid name for the collection.'));
	}

	$target_name = strtolower(sanitize_filename($name));

	$errors = $output = '';

	$collection_id = intval($collection_id);
	$thumbnail_id = intval($thumbnail_id);

	$name = mysql_real_escape_string($name);
	$description = mysql_real_escape_string($description);

	// Rename the directory
	// First, get the collection name of our source collection
	$sql = "SELECT c.path as collection_path, name
			FROM ".PLOGGER_TABLE_PREFIX."collections c
			WHERE c.id = '$collection_id'";

	$result = run_query($sql);
	$row = mysql_fetch_assoc($result);

	$source_collection_name = SmartStripSlashes($row['collection_path']);
	$source_path = $config['basedir'].'plog-content/images/'.$source_collection_name;
	$target_path = $config['basedir'].'plog-content/images/'.$target_name;

	// Check for self-renaming collection instance
	if ($source_path != $target_path) {
		// Do not allow collections with duplicate names, otherwise mod_rewritten links will start
		// to behave weird.
		if (is_dir($target_path)) {
			// If there is already a directory, check to see if it's in the database
			$collection_data = get_collection_by_name($name);
			if ($collection_data) {
				// It's in the database, so throw duplicate collection error
				return array('errors' => sprintf(plog_tr('Collection %s could not be renamed to %s, because there is another collection with that name.'), '<strong>'.$row['name'].'</strong>', '<strong>'.$name.'</strong>'));
			} else {
				// It's not in the database so attempt to delete the directory
				if (!kill_dir($target_path)) {
					// Could not delete the directory, so prompt the user to delete it manually
					return array('errors' => sprintf(plog_tr('Collection directory %s exists, but no collection exists in the database. Attempt to delete automatically failed. Please delete folder via FTP manually and try again.'), '<strong>'.$target_path.'</strong>'));
				}
			}
		}

		// Perform the rename on the directory
		if (!move_this($source_path, $target_path)) {
			return array('errors' => sprintf(plog_tr('Error renaming directory: %s to %s'), '<strong>'.$source_path.'</strong>', '<strong>'.$target_path.'</strong>'));
		}
	}

	$target_name = mysql_real_escape_string($target_name);

	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."collections SET name = '$name', path = '$target_name', description = '$description', thumbnail_id = '$thumbnail_id' WHERE id='$collection_id'";
	$result = mysql_query($query);
	if (!$result) {
		return array('errors' => mysql_error());
	}

	$output = plog_tr('You have successfully modified the selected collection.');

	// XXX: Update the path only if a collection was actually renamed

	// Update the path field for all pictures within that collection
	// Now we need to update the database paths of all pictures within source album
	$sql = "SELECT p.id AS id, p.path AS path, c.name AS collection_name, a.path AS album_path
		FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."pictures p, ".PLOGGER_TABLE_PREFIX."collections c
		WHERE p.parent_album = a.id AND p.parent_collection = c.id AND p.parent_collection = '$collection_id'";

	$result = run_query($sql);

	while($row = mysql_fetch_assoc($result)) {

		$filename = basename($row['path']);
		$album_path = $row['album_path'];

		$new_path = mysql_real_escape_string(SmartStripSlashes($target_name.'/'.$album_path.'/'.$filename));

		// Update database
		$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."pictures SET path = '$new_path' WHERE id = '$row[id]'";
		mysql_query($sql) or ($output .= mysql_error());
	}

	return array(
		'errors' => $errors,
		'output' => $output,
	);
}

function update_collection_field($collection_id, $field, $value) {
	$fields = array('name', 'description');
	if (!in_array($field, $fields)) {
		return array('errors' => plog_tr('Invalid action'));
	}

	$errors = $output = '';

	$collection_id = intval($collection_id);
	$value = mysql_real_escape_string(trim($value));

	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."collections SET $field = '$value' WHERE id='$collection_id'";

	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected collection.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected collection.'));
	}

}

function delete_collection($del_id) {
	global $config;
	$sql = "SELECT c.name AS collection_name, c.path AS collection_path, c.id AS collection_id
		FROM ".PLOGGER_TABLE_PREFIX."collections c
		WHERE c.id = '$del_id'";

	$result = run_query($sql);
	$collection = mysql_fetch_assoc($result);

	if (!$collection) {
		return array('errors' => plog_tr('No such collection.'));
	}

	// First delete all albums registered with this album
	$sql = 'SELECT * FROM '.PLOGGER_TABLE_PREFIX.'albums WHERE parent_id = '.$collection['collection_id'];
	$result = run_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		delete_album($row['id']);
	}

	// XXX: un-register collection
	$query = "DELETE FROM ".PLOGGER_TABLE_PREFIX."collections WHERE `id`= '".$collection['collection_id']."'";
	run_query($query);

	// Finally try to delete the directory itself. It will succeed, if there are no files left inside it ..
	// If there are then .. how did they get there? Probably not through Plogger and in this case do we 
	// really want to delete those?
	$source_collection_name = SmartStripSlashes($collection['collection_path']);

	// Delete any thumbnails for the collection
	$collection_thumb_directory = $config['basedir'].'plog-content/thumbs/'.$source_collection_name;
	if (file_exists($collection_thumb_directory)) {
		kill_dir($collection_thumb_directory);
	}
	// Check to see if the collection_directory is a real directory and then try to delete it
	$collection_directory = $config['basedir'].'plog-content/images/'.$source_collection_name;
	if (is_dir($collection_directory)) {
		if (!kill_dir($collection_directory)) {
			return array('errors' => plog_tr('Collection directory still contains files after all albums have been deleted.'));
		}
	} else {
		return array('errors' => plog_tr('Collection has invalid path, not deleting directory.'));
	}
	return array();
}

function add_album($album_name, $description, $pid) {
	global $config;
	$output = $errors = '';
	$id = 0;
	$album_name = trim(SmartStripSlashes($album_name));
	if (empty($album_name)) {
		return array('errors' => plog_tr('Please enter a valid name for the album.'));
	}
	// Get the parent collection name
	$query = "SELECT c.path as collection_path FROM ". PLOGGER_TABLE_PREFIX."collections c WHERE id = '$pid'";

	$result = run_query($query);
	$row = mysql_fetch_assoc($result);

	// This shouldn't happen
	if (empty($row)) {
		return array('errors' => plog_tr('No such collection.'));
	}

	$album_folder = strtolower(sanitize_filename($album_name));

	// First try to create the directory to hold the images, if that fails, then the album
	// will be unusable anyway
	$create_path = $config['basedir'].'plog-content/images/'.SmartStripSlashes($row['collection_path']).'/'.$album_folder;

	// Check path so we are not creating duplicate albums within the same collection
	if (is_dir($create_path)) {
		// If there is already a directory, check to see if it's in the database
		$album_data = get_album_by_name($album_name, $pid);
		if ($album_data) {
			// It's in the database, so throw duplicate album error
			return array('output' => 'existing', 'id' => $album_data['id'], 'errors' => sprintf(plog_tr('New album could not be created, because there is already one named %s in the collection %s'), '<strong>'.$album_folder.'</strong>', '<strong>'.ucfirst(SmartStripSlashes($row['collection_path']).'</strong>')));
		} else {
			// It's not in the database so attempt to delete the directory
			if (!kill_dir($create_path)) {
				// Could not delete the directory, so prompt the user to delete it manually
				return array('errors' => sprintf(plog_tr('Album directory %s exists, but no album exists in the database. Attempt to delete automatically failed. Please delete folder via FTP manually and try again.'), '<strong>'.$create_path.'</strong>'));
			}
		}
	}

	if (!makeDirs($create_path)) {
		$errors .= sprintf(plog_tr('Could not create directory %s!'), '<strong>'.$path.'</strong>');
	} else {
		$sql_name = mysql_real_escape_string($album_name);
		$description = mysql_real_escape_string($description);
		$album_folder = mysql_real_escape_string($album_folder);
		$query = "INSERT INTO ".PLOGGER_TABLE_PREFIX."albums (`name`,`description`,`parent_id`,`path`) VALUES ('$sql_name', '$description', '$pid', '$album_folder')";
		$result = run_query($query);
		$id = mysql_insert_id();

		$output .= sprintf(plog_tr('You have successfully created the album %s.'), '<strong>'.$album_name.'</strong>');
	}
	// Caller can check the value of id, if it is zero, then album creation failed
	// errors and output are separate, because this way the caller can format the return value
	// as it needs
	$result = array(
		'output' => $output,
		'errors' => $errors,
		'id' => $id,
	);
	return $result;
}

function update_album($album_id, $name, $description, $thumbnail_id = 0) {
	global $config;
	$errors = $output = '';

	$album_id = intval($album_id);
	$thumbnail_id = intval($thumbnail_id);
	$name = mysql_real_escape_string(SmartStripSlashes(trim($name)));
	$description = mysql_real_escape_string(SmartStripSlashes($description));
	if (empty($name)) {
		return array('errors' => plog_tr('Please enter a valid name for the album.'));
	}

	$target_name = strtolower(sanitize_filename(SmartStripSlashes($name)));

	// First, get the album name and collection name of our source album
	$sql = "SELECT c.path AS collection_path, a.path AS album_path, a.parent_id AS collection_id
			FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."collections c
			WHERE c.id = a.parent_id AND a.id = ".$album_id;

	$result = run_query($sql);
	$row = mysql_fetch_assoc($result);

	$source_album_name = SmartStripSlashes($row['album_path']);
	$source_collection_name = SmartStripSlashes($row['collection_path']);

	$source_path = $config['basedir'].'plog-content/images/'.$source_collection_name.'/'.$source_album_name;
	$target_path = $config['basedir'].'plog-content/images/'.$source_collection_name.'/'.$target_name;

	// Check for self-renaming album instance
	if ($source_path != $target_path) {
		// Check path so we are not creating duplicate albums within the same collection
		if (is_dir($target_path)) {
			// If there is already a directory, check to see if it's in the database
			$album_data = get_album_by_name($name, $row['collection_id']);
			if ($album_data) {
				// It's in the database, so throw duplicate album error
				return array('errors' => sprintf(plog_tr('New album could not be created, because there is already one named %s in the collection %s'), '<strong>'.$target_name.'</strong>', '<strong>'.$source_collection_name.'</strong>'));
			} else {
				// It's not in the database so attempt to delete the directory
				if (!kill_dir($target_path)) {
					// Could not delete the directory, so prompt the user to delete it manually
					return array('errors' => sprintf(plog_tr('Album directory %s exists, but no album exists in the database. Attempt to delete automatically failed. Please delete folder via FTP manually and try again.'), '<strong>'.$target_path.'</strong>'));
				}
			}
		}

		// Perform the rename on the directory
		if (!move_this($source_path, $target_path)) {
			return array(
				'errors' => sprintf(plog_tr('Error renaming directory: %s to %s'), '<strong>'.$source_path.'</strong>', '<strong>'.$target_path.'</strong>'));
		}
	}

	$target_name = mysql_real_escape_string($target_name);

	// Proceed only if rename succeeded
	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."albums SET
			name = '$name',
			description = '$description',
			thumbnail_id = '$thumbnail_id',
			path = '$target_name'
		WHERE id='$album_id'";

	$result = mysql_query($query);
	if (!$result) {
		return array('errors' => mysql_error());
	}

	$output .= plog_tr('You have successfully modified the selected album.');

	// Update the path field for all pictures within that album
	$sql = "SELECT p.path AS path, p.id AS id, c.name AS collection_name, a.name AS album_name
			FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."pictures p, ".PLOGGER_TABLE_PREFIX."collections c
			WHERE p.parent_album = a.id AND p.parent_collection = c.id AND p.parent_album = '$album_id'";

	$result = run_query($sql);

	while($row = mysql_fetch_assoc($result)) {

		$filename = basename($row['path']);
		$new_path = mysql_real_escape_string(SmartStripSlashes($source_collection_name.'/'.$target_name.'/'.$filename));

		// Update database
		$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."pictures SET path = '$new_path' WHERE id = '$row[id]'";
		mysql_query($sql) or ($errors .= mysql_error());
	}

	return array(
		'errors' => $errors,
		'output' => $output,
	);
}

function update_album_field($album_id, $field, $value) {
	$fields = array('name', 'description');
	if (!in_array($field, $fields)) {
		return array('errors' => plog_tr('Invalid action'));
	}

	$value = mysql_real_escape_string(trim(SmartStripSlashes($value)));
	$errors = $output = '';
	$album_id = intval($album_id);

	// Proceed only if rename succeeded
	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."albums SET
			$field = '$value'
		WHERE id='$album_id'";

	$result = mysql_query($query);

	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected album.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected album.'));
	}
}

function move_album($album_id, $to_collection) {
	global $config;

	$res = array(
		'errors' => '',
		'output' => '',
	);

	$album_id = intval($album_id);
	$to_collection = intval($to_collection);

	$sql = "SELECT
				c.path as collection_path,
				c.thumbnail_id as collection_thumb,
				c.id as collection_id,
				a.path as album_path
			FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."collections c
			WHERE c.id = a.parent_id AND a.id = '$album_id'";

	$result = run_query($sql);
	$row = mysql_fetch_assoc($result);

	$source_album_name = SmartStripSlashes($row['album_path']);
	$source_collection_name = SmartStripSlashes($row['collection_path']);
	$source_collection_thumb = $row['collection_thumb'];
	$source_collection_id = $row['collection_id'];

	// If moving to same collection, abort
	if ($to_collection == $source_collection_id) {
		return;
	}

	// Next, get the collection name of our destination collection
	$sql = "SELECT c.path as collection_path FROM ".PLOGGER_TABLE_PREFIX."collections c WHERE c.id = '$to_collection'";

	$result = run_query($sql);
	$row = mysql_fetch_assoc($result);

	$target_collection_name = SmartStripSlashes($row['collection_path']);
	$source_path = $config['basedir'].'plog-content/images/'.$source_collection_name.'/'.$source_album_name.'/';
	$target_path = $config['basedir'].'plog-content/images/'.$target_collection_name.'/'.$source_album_name.'/';
	$thumb_path = $config['basedir'].'plog-content/thumbs/'.$source_collection_name.'/'.$source_album_name.'/';

	// Check path so we are not creating duplicate albums within the same collection
	if (is_dir($target_path)) {
		// If there is already a directory, check to see if it's in the database
		$album_data = get_album_by_name($source_album_name, $to_collection);
		if ($album_data) {
			// It's in the database, so throw duplicate album error
			return array('errors' => sprintf(plog_tr('New album could not be created, because there is already one named %s in the collection %s'), '<strong>'.$source_album_name.'</strong>', '<strong>'.$target_collection_name.'</strong>'));
		} else {
			// It's not in the database so attempt to delete the directory
			if (!kill_dir($target_path)) {
				// Could not delete the directory, so prompt the user to delete it manually
				return array('errors' => sprintf(plog_tr('Album directory %s exists, but no album exists in the database. Attempt to delete automatically failed. Please delete folder via FTP manually and try again.'), '<strong>'.$target_path.'</strong>'));
			}
		}
	}

	// Attempt to make new album directory in target collection
	if (!makeDirs($target_path)) {
		return array('errors' => sprintf(plog_tr('Could not create directory %s!'), '<strong>'.$target_path.'</strong>'));
	}

	// Now we need to update the database paths of all pictures within source album
	$sql = "SELECT p.path as path, p.id as picture_id, c.name as collection_name, a.name as album_name
		FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."pictures p, ".PLOGGER_TABLE_PREFIX."collections c
		WHERE p.parent_album = a.id AND p.parent_collection = c.id AND p.parent_album = '$album_id'";

	$result = run_query($sql);
	$pic_ids = array();

	while($row = mysql_fetch_assoc($result)) {
		$filename = SmartStripSlashes(basename($row['path']));
		$pic_ids[] = $row['picture_id'];
		$old_path = $source_path.$filename;
		$new_path = $target_path.$filename;

		if (!move_this($old_path, $new_path)) {
			$res['errors'] .=  sprintf(plog_tr('Could not move file: %s to %s'), '<strong>'.$old_path.'</strong>', '<strong>'.$new_path.'</strong>');
		} else {
			@chmod($new_path, PLOGGER_CHMOD_FILE);
		}

		$path_insert = mysql_real_escape_string($target_collection_name.'/'.$source_album_name.'/'.$filename);

		$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."pictures SET
				parent_collection = '$to_collection',
				path = '$path_insert'
			WHERE id = '$row[picture_id]'";
		mysql_query($sql) or ($res['errors'] .= mysql_error());
	}

	// Check if collection thumbnail = picture moved to different collection and set to default if so
	if (in_array($source_collection_thumb, $pic_ids)) {
		$query = "UPDATE ".PLOGGER_TABLE_PREFIX."collections SET `thumbnail_id`='0' WHERE id='".$source_collection_id."'";
		run_query($query);
	}

	// Update the parent id of the moved album
	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."albums SET `parent_id` = '$to_collection' WHERE `id`='$album_id'";
	$result = run_query($query);

	// Attempt to delete the old folder and thumbnails if there were no errors moving the files
	if ($res['errors'] == '') {
		kill_dir($thumb_path);
		$remove = kill_dir($source_path);
		if (!$remove) {
			$res['errors'] .= sprintf(plog_tr('Could not remove album from collection %s. Album still contains files after all pictures have been moved.'), '<strong>'.$source_collection_name.'</strong>');
		}
	}
	return $res;
}

function delete_album($del_id) {
	global $config;
	$sql = "SELECT c.name AS collection_name, a.name AS album_name, a.id AS album_id, c.path AS collection_path, a.path AS album_path
		FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."collections c
		WHERE c.id = a.parent_id AND a.id = '$del_id'";

	$result = run_query($sql);
	$album = mysql_fetch_assoc($result);

	if (!$album) {
		return array('errors' => plog_tr('No such album'));
	}

	// First delete all pictures registered with this album
	$sql = 'SELECT * FROM '.PLOGGER_TABLE_PREFIX.'pictures WHERE parent_album = '.$album['album_id'];
	$result = run_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		delete_picture($row['id']);
	}

	// XXX: un-register album
	$query = "DELETE FROM ".PLOGGER_TABLE_PREFIX."albums WHERE `id`= '".$album['album_id']."'";
	run_query($query);

	// Finally try to delete the directory itself. It will succeed, if there are no files left inside it ..
	// If there are then .. how did they get there? Probably not through Plogger and in this case do we 
	// really want to delete those?
	$source_album_name = SmartStripSlashes($album['album_path']);
	$source_collection_name = SmartStripSlashes($album['collection_path']);

	// Delete any thumbnails for the album
	$album_thumb_directory = $config['basedir'].'plog-content/thumbs/'.$source_collection_name.'/'.$source_album_name;
	if (file_exists($album_thumb_directory)) {
		kill_dir($album_thumb_directory);
	}
	// Check to see if the album_directory is a real directory and then try to delete it
	$album_directory = $config['basedir'].'plog-content/images/'.$source_collection_name.'/'.$source_album_name;
	if (is_dir($album_directory)) {
		if (!kill_dir($album_directory)) {
			return array('errors' => plog_tr('Album directory still contains files after all pictures have been deleted.'));
		}

	} else {
		return array('errors' => plog_tr('Album has invalid path, not deleting directory.'));
	}
	return array();
}

function update_comment($id, $author, $email, $url, $comment) {
	$id = intval($id);
	$author = mysql_real_escape_string($author);
	$email = mysql_real_escape_string($email);
	$url = mysql_real_escape_string($url);
	$comment = mysql_real_escape_string(trim($comment));

	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."comments SET author = '$author', comment = '$comment', url = '$url', email = '$email' WHERE id='$id'";
	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected comment.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected comment.'));
	}
}

function update_comment_field($id, $field, $value) {
	$allowed_fields = array('author', 'email', 'url', 'comment');
	if (!in_array($field, $allowed_fields)) {
		return array('errors' => plog_tr('Invalid action'));
	}

	$id = intval($id);
	$value = mysql_real_escape_string($value);

	$query = "UPDATE ".PLOGGER_TABLE_PREFIX."comments SET $field = '$value' WHERE id='$id'";
	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected comment.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected comment.'));
	}
}

function count_albums($parent_id = 0) {
	if (!$parent_id)
		$numquery = "SELECT COUNT(*) AS `num_albums` FROM `".PLOGGER_TABLE_PREFIX."albums`";
	else
		$numquery = "SELECT COUNT(*) AS `num_albums` FROM `".PLOGGER_TABLE_PREFIX."albums` WHERE parent_id = '$parent_id'";

	$numresult = run_query($numquery);
	$num_albums = mysql_result($numresult, 0, 'num_albums');
	return $num_albums;
}

function count_collections() {

	$numquery = "SELECT COUNT(*) AS `num_collections` FROM `".PLOGGER_TABLE_PREFIX."collections`";

	$numresult = run_query($numquery);
	$num_albums = mysql_result($numresult, 0, 'num_collections');
	return $num_albums;
}

function count_pictures($parent_id = 0) {
	if (!$parent_id)
		$numquery = "SELECT COUNT(*) AS `num_pics` FROM `".PLOGGER_TABLE_PREFIX."pictures`";
	else
		$numquery = "SELECT COUNT(*) AS `num_pics` FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE parent_album = '$parent_id'";

	$numresult = run_query($numquery);
	$num_pics = mysql_result($numresult, 0, 'num_pics');
	return $num_pics;
}

function count_comments($parent_id = false) {
	$numquery = "SELECT COUNT(*) AS `num_comments` FROM `".PLOGGER_TABLE_PREFIX."comments` WHERE approved = 1";
	if ($parent_id !== false) {
		$numquery .= " AND parent_id = '".$parent_id."'";
	}

	$numresult = run_query($numquery);
	$num_comments = mysql_result($numresult, 0, 'num_comments');
	return $num_comments;
}

function plog_edit_comment_form($comment_id) {
	$output = '';
	$comment_id = intval($comment_id);
	$sql = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."comments c WHERE c.id = '$comment_id'";
	$result = run_query($sql);
	$comment = mysql_fetch_assoc($result);
	if (!is_array($comment)) {
		// XXX: return an error message instead
		return false;
	}
	$query = '';
	if (strpos($_SERVER['PHP_SELF'], 'plog-manage') !== false) {
		$query = '?level=comments&amp;id='.$comment['parent_id'];
	}

	$output .= "\n\t" . '<form class="edit width-700" action="'.$_SERVER['PHP_SELF'].$query.'" method="post">';

	// Get the thumbnail
	$photo = get_picture_by_id($comment['parent_id']);
	$thumbpath = generate_thumb(SmartStripSlashes($photo['path']), $photo['id'], THUMB_SMALL);
	$output .= "\n\t\t" . '<div style="float: right;"><img src="'.$thumbpath.'" alt="" /></div>
		<div>
			<div class="strong">'.plog_tr('Edit Comment').'</div>
			<p>
				<label class="strong" accesskey="a" for="author">'.plog_tr('Author').':</label><br />
				<input size="65" name="author" id="author" value="'.SmartStripSlashes($comment['author']).'" />
			</p>
			<p>
				<label class="strong" accesskey="e" for="email">'.plog_tr('Email').':</label><br />
				<input size="65" name="email" id="email" value="'.SmartStripSlashes($comment['email']).'" />
			</p>
			<p>
				<label class="strong" accesskey="u" for="url">'.plog_tr('Website').':</label><br />
				<input size="65" name="url" id="url" value="'.SmartStripSlashes($comment['url']).'" />
			</p>
			<p>
				<label class="strong" accesskey="c" for="comment">'.plog_tr('Comment').':</label><br />
				<textarea cols="62" rows="4" name="comment" id="comment">'.SmartStripSlashes($comment['comment']).'</textarea>
			</p>
			<input type="hidden" name="pid" value="'.$comment['id'].'" />
			<input type="hidden" name="action" value="update-comment" />
			<input class="submit" name="update" value="'.plog_tr('Update').'" type="submit" />
			<input class="submit-cancel" name="cancel" value="'.plog_tr('Cancel').'" type="submit" />
		</div>
	</form>' . "\n";
		return $output;
}

function makeDirs($path, $mode = PLOGGER_CHMOD_DIR) { // Creates directory tree recursively
	if (is_safe_mode()) {
		return is_dir($path) or (makeDirs(dirname($path), $mode) and makeDirs_ftp($path));
	} else {
		return is_dir($path) or (makeDirs(dirname($path), $mode) and mkdir($path, $mode) and configure_blank_index($path) and chmod($path, $mode));
	}
}

/** These functions are for safe_mode enabled servers **/
function connect_ftp() {
	global $config, $PLOGGER_FTP;

	$ftp_server = $config['ftp_host'];
	$ftp_user = $config['ftp_user'];
	$ftp_pass = $config['ftp_pass'];

	// Create connection
	$PLOGGER_FTP = ftp_connect($ftp_server); 
	// Login to ftp server
	$ftp_result = ftp_login($PLOGGER_FTP, $ftp_user, $ftp_pass);

	// Check if connection was made
	if ((!$PLOGGER_FTP) || (!$ftp_result)) {
		return false;
	}
	return true;
}

function close_ftp() {
	global $PLOGGER_FTP;

	if (isset($PLOGGER_FTP)) {
		ftp_close($PLOGGER_FTP);
	}
}

function makeDirs_ftp($path) {
	global $config, $PLOGGER_FTP;
	$return = false;

	$ftp_path = str_replace($config['basedir'], '', $path);
	$ftp_dir = dirname($ftp_path);
	$ftp_new_dir = str_replace($ftp_dir.'/', '', $ftp_path);

	if (!isset($PLOGGER_FTP)) {
		// Check if connection was made
		$ftp_connection = connect_ftp();
		if ($ftp_connection === false) {
			return $return;
		}
	}
	ftp_chdir($PLOGGER_FTP, $config['ftp_path'].$ftp_dir); // Go to destination dir
	$ftp_create_dir = ftp_mkdir($PLOGGER_FTP, $ftp_new_dir); // Create directory
	if ($ftp_create_dir) {
		chmod_ftp($path, 0777);
		configure_blank_index($path);
		$chmod = decoct(PLOGGER_CHMOD_DIR);
		$ftp_exec_dir = ftp_site($PLOGGER_FTP, 'CHMOD '.$chmod.' '.$ftp_new_dir.'/');
	}
	if ($ftp_exec_dir) {
		$return = true;
	} else {
		echo 'could not chmod!';
	}
	return $return;
}

function chmod_ftp($path, $mode = PLOGGER_CHMOD_DIR) {
	global $config, $PLOGGER_FTP;
	$return = false;

	$ftp_chmod_dir = str_replace($config['basedir'], $config['ftp_path'], $path);

	if (!isset($PLOGGER_FTP)) {
		// Check if connection was made
		$ftp_connection = connect_ftp();
		if ($ftp_connection === false) {
			return $return;
		}
	}
	$chmod = decoct($mode);
	$ftp_exec_dir = @ftp_site($PLOGGER_FTP, 'CHMOD '.$chmod.' '.$ftp_chmod_dir);
	if ($ftp_exec_dir) {
		$return = true;
	}
	return $return;
}
/** END functions for safe_mode enabled servers **/

function configure_htaccess_fullpic($allow = false) {
	$cfg = '';
	$placeholder_start = '# BEGIN Plogger';
	$placeholder_end = '# END Plogger';
	$thisfile =  '/plog-admin/'.basename(__FILE__);
	$adm = strpos($_SERVER['PHP_SELF'], '/plog-admin');
	$rewritebase = substr($_SERVER['PHP_SELF'], 0, $adm);
	if (!$allow) {
		$cfg .= "deny from all\n";
	}
	// Read the file
	global $config;
	$fpath = $config['basedir'].'plog-content/images/.htaccess';
	$htaccess_lines = (is_file($fpath)) ? @file($fpath) : array();

	$output = '';
	$configuration_placed = false;
	$between_placeholders = false;
	foreach($htaccess_lines as $line) {
		$tline = trim($line);
		if ($placeholder_start == $tline) {
			$between_placeholders = true;
			$output .= $line.$cfg;
			$configuration_placed = true;
			continue;
		}
		if ($placeholder_end == $tline) {
			$between_placeholders = false;
			$output .= $line;
			continue;
		}
		if ($between_placeholders) continue;

		$output .= $line;
	}

	// No placeholders? Append to the end
	if (!$configuration_placed) {
		$output .= "\n\n" .$placeholder_start. "\n" .$cfg.$placeholder_end. "\n";
	}

	$fh = @fopen($fpath, 'w');
	// Write changes out if the file can be opened.
	// XXX: perhaps plog-options.php should check whether settings can be written and warn the user if not?
	$success = false;
	if ($fh) {
		$success = true;
		fwrite($fh, $output);
		fclose($fh);
	}
	return $success;
}

function configure_mod_rewrite($enable = false) {
	global $config;

	if (file_exists($config['basedir'].'.htaccess') && is_writable($config['basedir'].'.htaccess')) {
		$cfg = '';
		$placeholder_start = '# BEGIN Plogger';
		$placeholder_end = '# END Plogger';
		$thisfile = '/plog-admin/'.basename(__FILE__);
		$adm = strpos($_SERVER['PHP_SELF'], '/plog-admin');
		$rewritebase = substr($_SERVER['PHP_SELF'], 0, $adm);
		if ($enable) {
			if (empty($rewritebase)) {
				$rewritebase = '/';
			}
			$cfg .= "<IfModule mod_rewrite.c>\n";
			$cfg .= "RewriteEngine on\n";
			$cfg .= "RewriteBase $rewritebase\n";
			$cfg .= "RewriteCond %{REQUEST_URI} !(\.|/\$)\n";
			$cfg .= "RewriteRule ^.*\$ http://".parse_url($config['gallery_url'], PHP_URL_HOST)."%{REQUEST_URI}/ [R=301,L]\n";
			if (strpos($config['gallery_url'], 'www.')) {
				$cfg .= "RewriteCond %{HTTP_HOST} !^www [NC]\n";
				$cfg .= "RewriteRule ^(.*)\$ ".$config['gallery_url']."\$1 [R=301,L]\n";
			}
			$cfg .= "RewriteCond %{REQUEST_FILENAME} -d [OR]\n";
			$cfg .= "RewriteCond %{REQUEST_FILENAME} -f\n";
			$cfg .= "RewriteRule ^.*$ - [S=2]\n";
			$cfg .= "RewriteRule feed/$ plog-rss.php?path=%{REQUEST_URI} [L]\n";
			$cfg .= "RewriteRule ^.*$ index.php?path=%{REQUEST_URI} [L]\n";
			$cfg .= "</IfModule>\n";
		}
		// Read the file
		global $config;
		$fpath = $config['basedir'].'.htaccess'; 
		$htaccess_lines = @file($fpath);

		$output = '';
		$configuration_placed = false;
		$between_placeholders = false;
		foreach($htaccess_lines as $line) {
			$tline = trim($line);
			if ($placeholder_start == $tline) {
				$between_placeholders = true;
				$output .= $line.$cfg;
				$configuration_placed = true;
				continue;
			}
			if ($placeholder_end == $tline) {
				$between_placeholders = false;
				$output .= $line;
				continue;
			}
			if ($between_placeholders) continue;

			$output .= $line;
		}

		// No placeholders? Append to the end
		if (!$configuration_placed) {
			$output .= "\n\n" .$placeholder_start. "\n" .$cfg.$placeholder_end. "\n";
		}

		$fh = @fopen($fpath, 'w');
		// Write changes out if the file can be opened.
		// XXX: perhaps plog-options.php should check whether settings can be written and warn the user if not?
		$success = false;
		if ($fh) {
			$success = true;
			fwrite($fh, $output);
			fclose($fh);
		}
		return $success;
	} else {
		return false;
	}
}

function configure_blank_index($fpath = '') {
	if (substr($fpath, -1) !== '/') {
		$fpath = $fpath.'/';
	}
	// Write out the default blank index.php
	if (!empty($fpath) && !file_exists($fpath.'index.php') && is_writable($fpath)) {
		$output = "<?php\n// Ignorance is bliss\n?>";
		$fh = @fopen($fpath.'index.php', 'w');
		if ($fh) {
			fwrite($fh, $output);
			fclose($fh);
		}
	}
	// Always return true because a blank index is not required
	return true;
}

// Makes sure that argument does not contain characters that cannot be allowed, like . or /, which
// could be used to point to directory or filenames outside the Plogger directory
function is_valid_directory($str) {
	// Allow only alfanumeric characters, hyphen, [, ], dot, apostrophe and space in collection names
	return !preg_match("/[^\w|\.|'|\-|\[|\] ]/", $str);
}

/// XXX: Something for the future: perhaps hooks for plugins should be implemented,
// so plugins could add new fields to all those forms.
function plog_add_collection_form() {
	$output = "\n\t\t" . '<input type="button" class="submit-create" id="show-collection" onclick="toggle(\'create-collection\'); toggle(\'show-collection\')" value="'.plog_tr('Create Collection').'" style="display: none;" />
		<form action="'.$_SERVER['PHP_SELF'].'" method="post">
		<div id="create-collection" class="cssbox-green">
			<div class="cssbox-head-green" onclick="toggle(\'create-collection\'); toggle(\'show-collection\')">
				<h2 class="manage">'.plog_tr('Create Collection').'</h2>
			</div><!-- /cssbox-head-green -->
			<div class="cssbox-body-green">
				<label accesskey="n" for="name">'.plog_tr('<em>N</em>ame').':</label><br />
				<input name="name" id="name" /><br />
				<label accesskey="d" for="description">'.plog_tr('<em>D</em>escription').':</label><br />
				<input name="description" id="description" size="47" style="width: 95%;" />
				<input name="action" type="hidden" value="add-collection" />
				<input class="submit" type="submit" value="'.plog_tr('Add Collection').'" />
			</div><!-- /cssbox-body-green -->
		</div><!-- /create-collection cssbox-green -->
		</form>
		<script type="text/javascript">toggle(\'create-collection\'); toggle(\'show-collection\');</script>' . "\n";
	return $output;
}

function plog_add_album_form($parent_collection) {
	$parent_collection = intval($parent_collection);
	$output = "\n\t\t" . '<input type="button" class="submit-create" id="show-album" onclick="toggle(\'create-album\'); toggle(\'show-album\')" value="'.plog_tr('Create Album').'" style="display: none;" />
		<form action="'.$_SERVER['PHP_SELF'].'?level=albums&amp;id='.$parent_collection.'" method="post">
		<div id="create-album" class="cssbox-green">
			<div class="cssbox-head-green" onclick="toggle(\'create-album\'); toggle(\'show-album\')">
				<h2 class="manage">'.plog_tr('Create Album').'</h2>
			</div><!-- /cssbox-head-green -->
			<div class="cssbox-body-green">
				<label accesskey="n" for="name">'.plog_tr('<em>N</em>ame').':</label><br />
				<input name="name" id="name" /><br />
				<label accesskey="d" for="description">'.plog_tr('<em>D</em>escription').':</label><br />
				<input name="description" id="description" size="47" style="width: 95%;" />
				<input name="action" type="hidden" value="add-album" />
				<input type="hidden" name="parent_collection" value="'.$parent_collection.'" />
				<input class="submit" type="submit" value="'.plog_tr('Add Album').'" />
			</div><!-- /cssbox-body-green -->
		</div><!-- /create-album cssbox-green -->
		</form>
		<script type="text/javascript">toggle(\'create-album\'); toggle(\'show-album\');</script>' . "\n";
	return $output;
}

function plog_edit_collection_form($collection_id) {
	global $config, $thumbnail_config;
	$output = '';
	$collection_id = intval($collection_id);

	$output .= "\n\t\t" . '<form class="edit width-700" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	$collection = get_collection_by_id($collection_id);

	$auto_graphic = $config['gallery_url'].'plog-admin/images/auto.gif';
	$images = "\n\t\t\t\t\t" . '<option class="thumboption" value="0" style="padding-left: 100px; background-image: url('.$auto_graphic.');">'.plog_tr('automatic').'</option>';

	// Create a list of all pictures in the collection. Should I create a separate function for this as well?
	$sql = "SELECT p.id AS id, caption, p.path AS path, a.name AS album_name
			FROM ".PLOGGER_TABLE_PREFIX."pictures p
			LEFT JOIN ".PLOGGER_TABLE_PREFIX."albums AS a ON p.parent_album = a.id
			WHERE p.parent_collection = '".$collection_id."'
			ORDER BY a.name, p.date_submitted";

	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$selected = ($row['id'] == $collection['thumbnail_id']) ? ' selected="selected"' : '';
		$style = 'class="thumboption" style="padding-left: '.($thumbnail_config[THUMB_SMALL]['size'] + 5).'px; background-image: url('.generate_thumb(SmartStripSlashes($row['path']), $row['id']).');"';

		$images .= "\n\t\t\t\t\t" . '<option '.$style.' value="'.$row['id'].'"'.$selected.'>';
		$images .= SmartStripSlashes($row['album_name']).": ";
		$images .= !empty($row['caption']) ? SmartStripSlashes($row['caption']) : SmartStripSlashes(basename($row['path']));
		$images .= "</option>";
	}

	$output .= "\n\t\t\t" . '<div>
				<div class="strong">'.plog_tr('Edit Collection Properties').'</div>
				<p>
					<label class="strong" accesskey="n" for="name">'.plog_tr('<em>N</em>ame').':</label><br />
					<input size="68" name="name" id="name" value="'.htmlspecialchars(SmartStripSlashes($collection['name'])).'" />
				</p>
				<p>
					<label class="strong" accesskey="d" for="description">'.plog_tr('<em>D</em>escription').':</label><br />
					<input size="68" name="description" id="description" value="'.htmlspecialchars(SmartStripSlashes($collection['description'])).'" />
				</p>
				<p>
					<span class="strong">Thumbnail:</span><br />
					<select name="thumbnail_id" onchange="updateThumbPreview(this)" class="thumbselect width-500" id="thumbselect">'.$images.'
					</select>
					<script type="text/javascript">updateThumbPreview(document.getElementById(\'thumbselect\'));</script>
				</p>
				<input type="hidden" name="pid" value="'.$collection_id.'" />
				<input type="hidden" name="action" value="update-collection" />
				<input class="submit" name="update" value="'.plog_tr('Update').'" type="submit" />
				<input class="submit-cancel" name="cancel" value="'.plog_tr('Cancel').'" type="submit" />
			</div>
		</form>' . "\n";
		return $output;
}

function plog_edit_album_form($album_id) {
	global $config, $thumbnail_config;

	$album_id = intval($album_id);

	$album = get_album_by_id($album_id);
	$auto_graphic = $config['gallery_url'].'plog-admin/images/auto.gif';

	$page = isset($_GET['plog_page']) ? '&amp;plog_page='.intval($_GET['plog_page']) : '';

	$output = "\n\t\t" . '<form class="edit width-700" action="'.$_SERVER['PHP_SELF'] .'?level=albums&amp;id='.$album['parent_id'].$page .'" method="post">';

	$images = '<option class="thumboption" value="0" style="padding-left: 100px; background-image: url('.$auto_graphic.');">'.plog_tr('automatic').'</option>';

	$sql = "SELECT id, caption, path FROM ".PLOGGER_TABLE_PREFIX."pictures p WHERE p.parent_album = '".$album_id."'";

	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
			$selected = ($row['id'] == $album['thumbnail_id']) ? ' selected="selected"' : '';
			$style = 'class="thumboption" style="padding-left: '.($thumbnail_config[THUMB_SMALL]['size'] + 5).'px; background-image: url('.generate_thumb(SmartStripSlashes($row['path']), $row['id']).');"';
			$images .= "\n\t\t\t\t" . '<option '.$style.' value="'.$row['id'].'"'.$selected.'>';
			$images .= !empty($row['caption']) ? SmartStripSlashes($row['caption']) : SmartStripSlashes(basename($row['path']));
			$images .= "</option>";
		}

		$output .= "\n\t\t\t" . '<div>
				<div class="strong">'.plog_tr('Edit Album Properties').'</div>
				<p>
					<label class="strong" for="name" accesskey="n">'.plog_tr('<em>N</em>ame').':</label><br />
					<input size="61" name="name" id="name" value="'.htmlspecialchars(SmartStripSlashes($album['name'])).'" />
				</p>
				<p>
					<label class="strong" for="description" accesskey="d">'.plog_tr('<em>D</em>escription').':</label><br />
					<input size="61" name="description" id="description" value="'.htmlspecialchars(SmartStripSlashes($album['description'])).'" />
				</p>
				<p>
					<span class="strong">Thumbnail:</span><br />
					<select name="thumbnail_id" class="thumbselect width-450" id="thumbselect" onchange="updateThumbPreview(this)">'.$images.'
					</select>
					<script type="text/javascript">updateThumbPreview(document.getElementById(\'thumbselect\'));</script>
				</p>
				<input type="hidden" name="pid" value="'.$album_id.'" />
				<input type="hidden" name="action" value="update-album" />
				<input class="submit" name="update" value="'.plog_tr('Update').'" type="submit" />
				<input class="submit-cancel" name="cancel" value="'.plog_tr('Cancel').'" type="submit" />
			</div>
		</form>' . "\n";
		return $output;
}

function plog_picture_manager($id, $from, $limit) {
	global $config, $empty;
	$output = '';

	plogger_init_pictures(array(
		'type' => 'album',
		'value' => $id,
		'from' => $from,
		'limit' => $limit,
		'sortby' => !empty($config['default_sortby']) ? $config['default_sortby'] : 'id',
		'sortdir' => !empty($config['default_sortdir']) ? $config['default_sortdir'] : 'ASC'
	));

	// Create javascript initiation function for editable elements
	if (plogger_has_pictures()) {
		$output .= "\n\t\t" . '<script type="text/javascript">';
		$output .= "\n\t\t\t" . 'Event.observe(window, \'load\', init, false);';
		$output .= "\n\t\t\t" . 'function init() {' . "\n";

		while(plogger_has_pictures()) {
			plogger_load_picture();
			$output .= "\t\t\t\tmakeEditable('picture-description-".plogger_get_picture_id()."');
				makeEditable('picture-caption-".plogger_get_picture_id()."');\n";
		}
		$output .= "\t\t\t" . '}';
		$output .= "\n\t\t" . '</script>';
	}

	// Reset the picture array
		plogger_init_pictures(array(
			'type' => 'album',
			'value' => $id,
			'from' => $from,
			'limit' => $limit,
			'sortby' => !empty($config['default_sortby']) ? $config['default_sortby'] : 'id',
			'sortdir' => !empty($config['default_sortdir']) ? $config['default_sortdir'] : 'ASC'
	));

	if (plogger_has_pictures()) {
		$allow_comment = ($config['allow_comments']) ? plog_tr('Allow Comments') : '&nbsp;';
		$output .= "\n\t\t" . '<table style="width: 100%;" cellpadding="3" cellspacing="0">
			<col style="width: 15px;" />
			<tr class="header">
				<th class="table-header-left align-center width-15"><input name="allbox" type="checkbox" onclick="checkToggle(document.getElementById(\'contentList\'));" /></th>
				<th class="table-header-middle align-center width-150">'.plog_tr('Thumb').'</th>
				<th class="table-header-middle align-left width-175">'.plog_tr('Filename').'</th>
				<th class="table-header-middle align-left">'.plog_tr('Caption').'/'.plog_tr('Description').'</th>
				<th class="table-header-middle align-center width-125">'.$allow_comment.'</th>
				<th class="table-header-right align-center width-100">'.plog_tr('Actions').'</th>
			</tr>';
		$counter = 0;

		while(plogger_has_pictures()) {
			if ($counter%2 == 0) $table_row_color = 'color-1';
			else $table_row_color = 'color-2';
			$counter++;
			plogger_load_picture();

			$id = plogger_get_picture_id();
			$output .= "\n\t\t\t" . '<tr class="'.$table_row_color.'">';
			$output .= "\n\t\t\t\t" . '<td class="align-center width-15"><p class="margin-5"><input type="checkbox" name="selected[]" value="'.$id.'" /></p></td>';
			$thumbpath = plogger_get_picture_thumb();
			$imgtag = '<img src="'.$thumbpath.'" title="'.plogger_get_picture_caption('clean').'" alt="'.plogger_get_picture_caption('clean').'" />';
			$output .= "\n\t\t\t\t" . '<td class="align-center width-150"><div class="img-shadow"><a href="'.plogger_get_picture_thumb(THUMB_LARGE).'" rel="lightbox" title="'.plogger_get_picture_caption('code').'">'.$imgtag.'</a></div></td>';
			$output .= "\n\t\t\t\t" . '<td class="align-left width-175"><p class="margin-5"><strong><a href="'.$_SERVER['PHP_SELF'].'?level=comments&amp;id='.$id.'">'.basename(plogger_get_source_picture_path()).'</a></strong><br /><br /><span>'.sprintf(plog_tr('Comments: %d'), plogger_picture_comment_count()).'</span></p></td>';
			$output .= "\n\t\t\t\t" . '<td class="align-left vertical-top">
					<p class="margin-5 no-margin-bottom"><strong>'.plog_tr('Caption').':</strong></p>
					<p class="margin-5 no-margin-top" id="picture-caption-'.plogger_get_picture_id().'">'.plogger_get_picture_caption().'</p>
					<p class="margin-5 no-margin-bottom"><strong>'.plog_tr('Description').':</strong></p>
					<p class="margin-5 no-margin-top" id="picture-description-'.plogger_get_picture_id().'">'.plogger_get_picture_description().'</p>
				</td>';
			if ($config['allow_comments']) {
				$allow_comments = (1 == plogger_picture_allows_comments()) ? plog_tr('Yes') : plog_tr('No');
			} else {
				$allow_comments = '&nbsp;';
			}
			$output .= "\n\t\t\t\t" . '<td class="align-center width-125"><p class="margin-5">'.$allow_comments.'</p></td>';
			$output .= "\n\t\t\t\t" . '<td class="align-center width-100"><p class="margin-5"><a href="?action=edit-picture&amp;id='.$id;
			if (isset($_GET['entries_per_page'])) $output .= '&amp;entries_per_page='.intval($_GET['entries_per_page']);
			if (isset($_GET['plog_page'])) $output .= '&amp;plog_page='.intval($_GET['plog_page']);
			$output .= '"><img style="display: inline;" src="'.$config['gallery_url'].'plog-admin/images/edit.gif" alt="'.plog_tr('Edit').'" title="'.plog_tr('Edit').'" /></a>';
			$parent_id = $_REQUEST['id'];
			$output .= '&nbsp;&nbsp;&nbsp;<a href="?action=move-delete&amp;selected%5B%5D='.$id.'&amp;level=pictures&amp;delete_checked=1&amp;id='.$parent_id;
			if (isset($_GET['plog_page'])) $output .= '&amp;plog_page='.intval($_GET['plog_page']);
			$output .= '" onclick="return confirm(\''.plog_tr('Are you sure you want to delete this item?').'\');"><img style="display: inline;" src="'.$config['gallery_url'].'plog-admin/images/x.gif" alt="'.plog_tr('Delete').'" title="'.plog_tr('Delete').'" /></a></p></td>';
			$output .= "\n\t\t\t" . '</tr>';
		}

		$output .= "\n\t\t\t" . '<tr class="footer">
				<td class="align-left invert-selection" colspan="6"><a href="#" onclick="checkToggle(document.getElementById(\'contentList\')); return false;">'.plog_tr('Toggle Checkbox Selection').'</a></td>
			</tr>
		</table>' . "\n";
	} else {
		$output .= "\n\n\t\t" . '<p class="actions">'.sprintf(plog_tr('Sadly, there are no pictures yet. Why don\'t you <a title="upload images" href="%s" style="font-weight: bold;">upload some</a>?'), 'plog-upload.php').'</p>' . "\n";
		$empty = true;
	}
	return $output;
}

function plog_album_manager($id, $from, $limit) {
	global $config, $empty;
	$output = '';

	plogger_init_albums(array(
		'from' => $from,
		'collection_id' => $id,
		'limit' => $limit,
		'all_albums' => 1,
		'sortby' => !empty($config['album_sortby']) ? $config['album_sortby'] : 'id',
		'sortdir' => !empty($config['album_sortdir']) ? $config['album_sortdir'] : 'ASC'
	));

	// Create javascript initiation function for editable elements
	if (plogger_has_albums()) {
		$output .= "\n\t\t" . '<script type="text/javascript">';
		$output .= "\n\t\t\t" . 'Event.observe(window, \'load\', init, false);';
		$output .= "\n\t\t\t" . 'function init() {' . "\n";
		while(plogger_has_albums()) {
			plogger_load_album();
			// makeEditable('album-name-".plogger_get_album_id()."');
			$output .= "\t\t\t\tmakeEditable('album-description-".plogger_get_album_id()."');\n";
		}
		$output .= "\t\t\t" . '}';
		$output .= "\n\t\t" . '</script>';
	}

	plogger_init_albums(array(
		'from' => $from,
		'collection_id' => $id,
		'limit' => $limit,
		'all_albums' => 1,
		'sortby' => !empty($config['album_sortby']) ? $config['album_sortby'] : 'id',
		'sortdir' => !empty($config['album_sortdir']) ? $config['album_sortdir'] : 'ASC'
	));

	if (plogger_has_albums()) {
		$output .= "\n\t\t" . '<table style="width: 100%;" cellpadding="3" cellspacing="0">
			<col style="width: 15px;" />
			<tr class="header">
				<th class="table-header-left align-center width-15"><input name="allbox" type="checkbox" onclick="checkAll(document.getElementById(\'contentList\'));" /></th>
				<th class="table-header-middle align-left width-275">'.plog_tr('Name').'</th>
				<th class="table-header-middle align-left">'.plog_tr('Description').'</th>
				<th class="table-header-right align-center width-100">'.plog_tr('Actions').'</th>
			</tr>';
		$counter = 0;

		while(plogger_has_albums()) {
			plogger_load_album();
			$id = plogger_get_album_id();
			if ($counter%2 == 0) $table_row_color = 'color-1';
			else $table_row_color = 'color-2';
			$counter++;

			$text = (plogger_album_picture_count() == 1) ? plog_tr('image') : plog_tr('images');
			$output .= "\n\t\t\t" . '<tr class="'.$table_row_color.'">';
			$output .= "\n\t\t\t\t" . '<td class="align-center width-15"><p class="margin-5"><input type="checkbox" name="selected[]" value="'.$id.'" /></p></td>';
			$output .= "\n\t\t\t\t" . '<td class="align-left width-275"><p class="margin-5"><a class="folder" href="'.$_SERVER['PHP_SELF'].'?level=pictures&amp;id='.$id.'"><span id="album-name-'.plogger_get_album_id().'"><strong>'.plogger_get_album_name().'</strong></span></a> - '.sprintf(plog_tr('%d'), plogger_album_picture_count()).' '.$text.'</p></td>';
			$output .= "\n\t\t\t\t" . '<td class="align-left vertical-top"><p class="margin-5" id="album-description-'.plogger_get_album_id().'">'.plogger_get_album_description().'</p></td>';
			$page = (isset($_GET['plog_page'])) ? '&amp;plog_page='.intval($_GET['plog_page']) : '';
			$output .= "\n\t\t\t\t" . '<td class="align-center width-100"><p class="margin-5"><a href="'.$_SERVER['PHP_SELF'].'?action=edit-album&amp;id='.$id.$page.'"><img style="display: inline;" src="'.$config['gallery_url'].'plog-admin/images/edit.gif" alt="'.plog_tr('Edit').'" title="'.plog_tr('Edit').'" /></a>';
			$output .= '&nbsp;&nbsp;&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?action=move-delete&amp;selected%5B%5D='.$id.'&amp;level=albums&amp;delete_checked=1&amp;id='.$_REQUEST['id'].$page;
			$output .= '" onclick="return confirm(\''.plog_tr('Are you sure you want to delete this item?').'\');"><img style="display: inline;" src="'.$config['gallery_url'].'plog-admin/images/x.gif" alt="'.plog_tr('Delete').'" title="'.plog_tr('Delete').'" /></a></p></td>';
			$output .= "\n\t\t\t" . '</tr>';
		}

		$output .= "\n\t\t\t" . '<tr class="footer">
				<td class="align-left invert-selection" colspan="7"><a href="#" onclick="checkToggle(document.getElementById(\'contentList\')); return false;">'.plog_tr('Toggle Checkbox Selection').'</a></td>
			</tr>
		</table>' . "\n";
	} else {
		$output .= "\n\n\t\t" . '<p class="actions">'.plog_tr('There are no albums in this collection yet, why don\'t you create one?').'</p>' . "\n";
		$empty = true;
	}
	return $output;
}

function plog_collection_manager($from, $limit) {
	global $config, $empty;
	$output = '';

	plogger_init_collections(array(
		'from' => $from,
		'limit' => $limit,
		'all_collections' => 1,
		'sortby' => !empty($config['collection_sortby']) ? $config['collection_sortby'] : 'id',
		'sortdir' => !empty($config['collection_sortdir']) ? $config['collection_sortdir'] : 'ASC'
	));

	// Create javascript initiation function for editable elements
	if (plogger_has_collections()) {
		$output .= "\n\t\t" . '<script type="text/javascript">';
		$output .= "\n\t\t\t" . 'Event.observe(window, \'load\', init, false);';
		$output .= "\n\t\t\t" . 'function init() {' . "\n";
		while(plogger_has_collections()) {
			plogger_load_collection();
			// makeEditable('collection-name-".plogger_get_collection_id()."');
			$output .= "\t\t\t\tmakeEditable('collection-description-".plogger_get_collection_id()."');\n";
		}
		$output .= "\t\t\t" . '}';
		$output .= "\n\t\t" . '</script>';
	}

	plogger_init_collections(array(
		'from' => $from,
		'limit' => $limit,
		'all_collections' => 1,
		'sortby' => !empty($config['collection_sortby']) ? $config['collection_sortby'] : 'id',
		'sortdir' => !empty($config['collection_sortdir']) ? $config['collection_sortdir'] : 'ASC'
	));

	if (plogger_has_collections()) {
		$output .= "\n\t\t" . '<table style="width: 100%;" cellpadding="3" cellspacing="0">
			<col style="width: 15px;" />
			<tr class="header">
				<th class="table-header-left align-center width-15"><input name="allbox" type="checkbox" onclick="checkAll(document.getElementById(\'contentList\'));" /></th>
				<th class="table-header-middle align-left width-275">'.plog_tr('Name').'</th>
				<th class="table-header-middle align-left">'.plog_tr('Description').'</th>
				<th class="table-header-right align-center width-100">'.plog_tr('Actions').'</th>
			</tr>';
		$counter = 0;

		while(plogger_has_collections()) {
			plogger_load_collection();
			if ($counter%2 == 0) $table_row_color = 'color-1';
			else $table_row_color = 'color-2';
			$counter++;

			$id = plogger_get_collection_id();
			$text = (plogger_collection_album_count() == 1) ? plog_tr('album') : plog_tr('albums');
			$output .= "\n\t\t\t" . '<tr class="'.$table_row_color.'">';
			$output .= "\n\t\t\t\t" . '<td class="align-center width-15"><p class="margin-5"><input type="checkbox" name="selected[]" value="'.$id.'" /></p></td>';
			$output .= "\n\t\t\t\t" . '<td class="align-left width-275"><p class="margin-5"><a class="folder" href="?level=albums&amp;id='.$id.'"><span id="collection-name-'.plogger_get_collection_id().'"><strong>'.plogger_get_collection_name().'</strong></span></a> - '.sprintf(plog_tr('%d'), plogger_collection_album_count()).' '.$text.'</p></td>';
			$output .= "\n\t\t\t\t" . '<td class="align-left vertical-top"><p class="margin-5" id="collection-description-'.plogger_get_collection_id().'">'.plogger_get_collection_description().'</p></td>';
			$output .= "\n\t\t\t\t" . '<td class="align-center width-100"><p class="margin-5"><a href="?action=edit-collection&amp;id='.$id.'"><img style="display: inline;" src="'.$config['gallery_url'].'plog-admin/images/edit.gif" alt="'.plog_tr('Edit').'" title="'.plog_tr('Edit').'" /></a>';
			$output .= '&nbsp;&nbsp;&nbsp;<a href="?action=move-delete&amp;selected%5B%5D='.$id.'&amp;level=collections&amp;delete_checked=1&amp;';
			if (isset($_REQUEST['id'])) { $output .= 'id='.intval($_REQUEST['id']); }
			if (isset($_GET['plog_page'])) { $output .= '&amp;plog_page='.intval($_GET['plog_page']); }
			$output .= '" onclick="return confirm(\''.plog_tr('Are you sure you want to delete this item?').'\');"><img style="display: inline;" src="'.$config['gallery_url'].'plog-admin/images/x.gif" alt="'.plog_tr('Delete').'" title="'.plog_tr('Delete').'" /></a></p></td>';
			$output .= "\n\t\t\t" . '</tr>';
		}

		$output .= "\n\t\t\t" . '<tr class="footer">
				<td class="align-left invert-selection" colspan="7"><a href="#" onclick="checkToggle(document.getElementById(\'contentList\')); return false;">'.plog_tr('Toggle Checkbox Selection').'</a></td>
			</tr>
		</table>' . "\n";
	} else {
		$output .= "\n\n\t\t" . '<p class="actions">'.plog_tr('There are no collections yet').'.</p>' . "\n";
		$empty = true;
	}
	return $output;
}

function plog_comment_manager($id, $from, $limit) {
	global $config, $empty;
	$output = '';

	plogger_init_picture(array(
		'id' => $id,
		'from' => $from,
		'limit' => $limit
	));

	// Create javascript initiation function for editable elements
	if (plogger_picture_has_comments()) {
		$output .= "\n\t\t" . '<script type="text/javascript">';
		$output .= "\n\t\t\t" . 'Event.observe(window, \'load\', init, false);';
		$output .= "\n\t\t\t" . 'function init() {';
		while(plogger_picture_has_comments()) {
			plogger_load_comment();
			// makeEditable('picture".plogger_get_picture_id()."');
			$output .= "
				makeEditable('comment-comment-".plogger_get_comment_id()."');
				makeEditable('comment-author-".plogger_get_comment_id()."');
				makeEditable('comment-url-".plogger_get_comment_id()."');
				makeEditable('comment-email-".plogger_get_comment_id()."');";
		}
		$output .= "\n\t\t\t" . '}';
		$output .= "\n\t\t" . '</script>';
	}

	plogger_init_picture(array(
		'id' => $id,
		'from' => $from,
		'limit' => $limit
	));

	if (plogger_picture_has_comments()) {
		$output .= "\n\t\t" . '<table style="width: 100%;" cellpadding="3" cellspacing="0">
			<col style="width: 15px;" />
			<tbody>
				<tr class="header">
					<th class="table-header-left align-center width-15"><input name="allbox" type="checkbox" onclick="checkAll(document.getElementById(\'contentList\'));" /></th>
					<th class="table-header-middle align-left width-175">'.plog_tr('Author').'/'.plog_tr('Email').'/'.plog_tr('Website').'</th>
					<th class="table-header-middle align-left width-150">'.plog_tr('Date').'</th>
					<th class="table-header-middle align-left">'.plog_tr('Comment').'</th>
					<th class="table-header-right align-center width-100">'.plog_tr('Actions').'</th>
				</tr>';
		$counter = 0;

		while(plogger_picture_has_comments()) {
			plogger_load_comment();
			if ($counter%2 == 0) $table_row_color = 'color-1';
			else $table_row_color = 'color-2';
			$counter++;

			$id = plogger_get_comment_id();
			$output .= "\n\t\t\t\t" . '<tr class="'.$table_row_color.'">';
			$output .= "\n\t\t\t\t\t" .'<td class="align-center width-15"><p class="margin-5"><input type="checkbox" name="selected[]" value="'.$id.'" /></p></td>';
			$output .= "\n\t\t\t\t\t" . '<td class="align-left width-175">
						<p class="margin-5 no-margin-bottom"><strong>'.plog_tr('Author').':</strong></p>
						<p class="margin-5 no-margin-top" id="comment-author-'.$id.'">'.plogger_get_comment_author().'</p>
						<p class="margin-5 no-margin-bottom"><strong>'.plog_tr('Email').':</strong></p>
						<p class="margin-5 no-margin-top" id="comment-email-'.$id.'">'.plogger_get_comment_email().'</p>
						<p class="margin-5 no-margin-bottom"><strong>'.plog_tr('Website').':</strong></p>
						<p class="margin-5 no-margin-top" id="comment-url-'.$id.'">'.plogger_get_comment_url().'</p>
					</td>';
			$output .= "\n\t\t\t\t\t" . '<td class="align-left width-150"><p class="margin-5">'.plogger_get_comment_date('n/j/Y g:i a').'</p></td>';
			$output .= "\n\t\t\t\t\t" . '<td class="align-left vertical-top"><p class="margin-5" id="comment-comment-'.$id.'">'.plogger_get_comment_text().'</p></td>';
			$output .= "\n\t\t\t\t\t" . '<td class="align-center width-100"><p class="margin-5"><a href="?action=edit-comment&amp;id='.$id.'"><img style="display: inline;" src="'.$config['gallery_url'].'plog-admin/images/edit.gif" alt="'.plog_tr('Edit').'" title="'.plog_tr('Edit').'" /></a>';
		$output .= '&nbsp;&nbsp;&nbsp;<a href="?action=delete-comment&amp;id='.$id.'" onclick="return confirm(\''.plog_tr('Are you sure you want to delete this item?').'\');"><img style="display: inline;" src="'.$config['gallery_url'].'plog-admin/images/x.gif" alt="'.plog_tr('Delete').'" title="'.plog_tr('Delete').'" /></a></p></td>';
			$output .= "\n\t\t\t\t" . '</tr>';
	}

	$output .= "\n\t\t\t\t" . '<tr class="footer">
					<td class="align-left invert-selection" colspan="5"><a href="#" onclick="checkToggle(document.getElementById(\'contentList\')); return false;">'.plog_tr('Toggle Checkbox Selection').'</a></td>
				</tr>
			</tbody>
		</table>' . "\n";
	} else {
		$output .= "\n\n\t\t" . '<p class="actions">'.plog_tr('This picture has no comments.').'</p>' . "\n";
		$empty = true;
	}
	return $output;
}

function generate_ajax_picture_editing_init() {
	$output = '<script type="text/javascript">';
}

function plogger_show_server_info_link() {
	if (isset($_SESSION['plogger_logged_in'])) {
		return '<a id="show_server_info" accesskey="s" href="#" style="display: inline;" onclick="toggle(\'server-info, hide_server_info, show_server_info\');">'.plog_tr('Show server info').'</a><a id="hide_server_info" accesskey="s" href="#" style="display: none;" onclick="toggle(\'server-info, hide_server_info, show_server_info\');">'.plog_tr('Hide server info').'</a>';
	}
	return false;
}

function plogger_generate_server_info() {
	global $config;

	if (isset($_SESSION['plogger_logged_in'])) {
		$server_data = '<div id="server-info" style="display: none;">';

		$arg = explode('/', $_SERVER['SERVER_SOFTWARE']);
		$software_type = isset($arg[0]) ? $arg[0] : '';
		$software_version = isset($arg[1]) ? $arg[1] : '';
		$software_distro = isset($arg[2]) ? $arg[2] : '';

		$server_data .= "\n\t\t\t" . '<strong>'.plog_tr('Server Software').':</strong> '.$software_type.'/'.$software_version.' '.$software_distro.'<br />
			<strong>'.plog_tr('PHP Version').':</strong> '.phpversion().' ('.strtoupper(php_sapi_name()).')<br />
			<strong>'.plog_tr('MySQL Version').':</strong> '.mysql_get_server_info().'<br />
			<strong>'.plog_tr('GD Version').':</strong>';

/* Thanks to the Pixelpost Crew for the gd_info code below */
			if(function_exists('gd_info')) {
				$gd_info1 = gd_info();
				$gd_info = $gd_info1['GD Version'];
				if($gd_info == "") {
					$gd_info = plog_tr('Not installed');
				} else if ($gd_info1['JPG Support']) {
					$gd_info .= plog_tr(' with JPEG support');
				}
			}
		// Determine the limiting setting for upload sizes
		$max_upload = intval(ini_get('upload_max_filesize'));
		$max_post = intval(ini_get('post_max_size')) * 0.75;
		$file_limit = ($max_upload < $max_post) ? $max_upload.'MB' : $max_post.'MB';

		$server_data .= ' '.$gd_info.'<br />
			<strong>'.plog_tr('Session Save Path').':</strong> '.session_save_path().'<br />
			<strong>'.plog_tr('File Upload Size Limit').':</strong> '.$file_limit.'<br />
			<strong>'.plog_tr('Temporary Memory Limit').':</strong> '.ini_get('memory_limit').'<br />
			<strong>'.plog_tr('Code Run Time Limit').':</strong> '.ini_get('max_execution_time').'s<br />';
		if (is_safe_mode()) {
			$server_data .= "\n\t\t\t" . '<strong>safe_mode enabled</strong><br />';
		}
		$server_data .= "\n\t\t" . '</div><!-- /server-info -->';

		return $server_data;
	}
	return false;
}

?>