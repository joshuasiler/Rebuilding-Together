<?php

/* This file handles the generation of zip file for downloads. */

include(dirname(__FILE__).'/plog-load-config.php');
if (!$config['allow_dl']) {
	// ignorance is bliss
	exit();
}

/*
Zip file creation class
makes zip files on the fly...

use the functions add_dir() and add_file() to build the zip file;
see example code below

by Eric Mueller
http://www.themepark.com

v1.1 9-20-01
- added comments to example

v1.0 2-5-01

initial version with:
- class appearance
- add_file() and file() methods
- gzcompress() output hacking
by Denis O.Philippov, webmaster@atlant.ru, http://www.atlant.ru

official ZIP file format: http://www.pkware.com/appnote.txt
*/

class zipfile {

	var $datasec = array();							// array to store compressed data
	var $ctrl_dir = array();								// central directory
	var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00"; // end of Central directory record
	var $old_offset = 0;

	function add_dir($name) {
	// adds 'directory' to archive - do this before putting any files in directory!
	// $name - name of directory... like this: 'path/'
	// ...then you can add files using add_file with names like 'path/file.txt'

		$name = str_replace("\\", "/", $name);

		$fr =  "\x50\x4b\x03\x04";
		$fr .= "\x0a\x00";								// ver needed to extract
		$fr .= "\x00\x00";								// gen purpose bit flag
		$fr .= "\x00\x00";								// compression method
		$fr .= "\x00\x00\x00\x00";					// last mod time and date

		$fr .= pack("V", 0);							// crc32
		$fr .= pack("V", 0);							// compressed filesize
		$fr .= pack("V", 0);							// uncompressed filesize
		$fr .= pack("v", strlen($name) );			// length of pathname
		$fr .= pack("v", 0 );							// extra field length
		$fr .= $name;
		// end of 'local file header' segment

		// no 'file data' segment for path

		// "data descriptor" segment (optional but necessary if archive is not served as file)
		$fr .= pack("V", $crc);						// crc32
		$fr .= pack("V", $c_len);					// compressed filesize
		$fr .= pack("V", $unc_len);				// uncompressed filesize

		// add this entry to array
		$this -> datasec[] = $fr;

		$new_offset = $this->old_offset + strlen ($fr) ;
		//$new_offset = strlen(implode("", $this->datasec));

		// ext. file attributes mirrors MS-DOS directory attr byte, detailed
		// at http://support.microsoft.com/support/kb/articles/Q125/0/19.asp

		// now add to central record
		$cdrec =  "\x50\x4b\x01\x02";
		$cdrec .= "\x00\x00";						// version made by
		$cdrec .= "\x0a\x00";						// version needed to extract
		$cdrec .= "\x00\x00";						// gen purpose bit flag
		$cdrec .= "\x00\x00";						// compression method
		$cdrec .= "\x00\x00\x00\x00";			// last mod time & date
		$cdrec .= pack("V", 0);						// crc32
		$cdrec .= pack("V", 0);						// compressed filesize
		$cdrec .= pack("V", 0);						// uncompressed filesize
		$cdrec .= pack("v", strlen($name) );	// length of filename
		$cdrec .= pack("v", 0 );					// extra field length
		$cdrec .= pack("v", 0 );					// file comment length
		$cdrec .= pack("v", 0 );					// disk number start
		$cdrec .= pack("v", 0 );					// internal file attributes
		$ext = "\x00\x00\x10\x00";
		$ext = "\xff\xff\xff\xff";
		$cdrec .= pack("V", 16 );					// external file attributes  - 'directory' bit set

		$cdrec .= pack("V", $this -> old_offset ); // relative offset of local header
		$this -> old_offset = $new_offset;

		$cdrec .= $name;
		// optional extra field, file comment goes here
		// save to array
		$this -> ctrl_dir[] = $cdrec;

	}

	function add_file($data, $name) {
	// adds 'file' to archive
	// $data - file contents
	// $name - name of file in archive. Add path if you want

		$name = str_replace("\\", "/", $name);
		//$name = str_replace("\\", "\\\\", $name);

		$fr =  "\x50\x4b\x03\x04";
		$fr .= "\x14\x00";								// ver needed to extract
		$fr .= "\x00\x00";								// gen purpose bit flag
		$fr .= "\x08\x00";								// compression method
		$fr .= "\x00\x00\x00\x00";					// last mod time and date

		$unc_len = strlen($data);
		$crc = crc32($data);
		$zdata = gzcompress($data);
		$zdata = substr( substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
		$c_len = strlen($zdata);
		$fr .= pack("V", $crc);						// crc32
		$fr .= pack("V", $c_len);					// compressed filesize
		$fr .= pack("V", $unc_len);				// uncompressed filesize
		$fr .= pack("v", strlen($name) );			// length of filename
		$fr .= pack("v", 0 );							// extra field length
		$fr .= $name;
		// end of 'local file header' segment

		// 'file data' segment
		$fr .= $zdata;

		// "data descriptor" segment (optional but necessary if archive is not served as file)
		$fr .= pack("V", $crc);						// crc32
		$fr .= pack("V", $c_len);					// compressed filesize
		$fr .= pack("V", $unc_len);				// uncompressed filesize

		// add this entry to array
		$this -> datasec[] = $fr;

		$new_offset = strlen(implode("", $this->datasec));

		// now add to central directory record
		$cdrec =  "\x50\x4b\x01\x02";
		$cdrec .= "\x00\x00";						// version made by
		$cdrec .= "\x14\x00";						// version needed to extract
		$cdrec .= "\x00\x00";						// gen purpose bit flag
		$cdrec .= "\x08\x00";						// compression method
		$cdrec .= "\x00\x00\x00\x00";			// last mod time & date
		$cdrec .= pack("V", $crc);					// crc32
		$cdrec .= pack("V", $c_len);				// compressed filesize
		$cdrec .= pack("V", $unc_len);			// uncompressed filesize
		$cdrec .= pack("v", strlen($name) );	// length of filename
		$cdrec .= pack("v", 0 );					// extra field length
		$cdrec .= pack("v", 0 );					// file comment length
		$cdrec .= pack("v", 0 );					// disk number start
		$cdrec .= pack("v", 0 );					// internal file attributes
		$cdrec .= pack("V", 32 );					// external file attributes - 'archive' bit set

		$cdrec .= pack("V", $this -> old_offset ); // relative offset of local header
		// &n // bsp; echo "old offset is ".$this->old_offset.", new offset is $new_offset<br />";
		$this -> old_offset = $new_offset;

		$cdrec .= $name;
		// optional extra field, file comment goes here
		// save to central directory
		$this -> ctrl_dir[] = $cdrec;
	}

	// dump out file
	function file() {
		$data = implode("", $this -> datasec);
		$ctrldir = implode("", $this -> ctrl_dir);

		return
		$data.
		$ctrldir.
		$this -> eof_ctrl_dir.
		pack("v", sizeof($this -> ctrl_dir)).		// total # of entries "on this disk"
		pack("v", sizeof($this -> ctrl_dir)).		// total # of entries overall
		pack("V", strlen($ctrldir)).					// size of central dir
		pack("V", strlen($data)).					// offset to start of central dir
		"\x00\x00";										// .zip file comment length
	}
}

connect_db();

if (!isset($_REQUEST['checked']) || (!is_array($_REQUEST['checked']))) {
	echo 'No pictures were selected.';
} else {
	create_zip($_REQUEST['checked'], $_REQUEST['dl_type']);
}

close_db();

function create_zip($checked, $level) {
	global $zipfile;

	$dir = 'Plogger.'.date('Y.m.d').'/';

	$zipfile = new zipfile();
	// add the subdirectory ... important!
	// $zipfile -> add_dir($dir);

	add_photos($checked, $level, $dir, $zipfile);

	$output = $zipfile -> file();

	// the next lines attempt to clear the cache, get the filesize, and force an immediate download of the zip file:
	header('Pragma: public');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private', false);
	header('Content-type: application/octet-stream');
	header('Content-disposition: attachment; filename=plog-package-'.date('Y.m.d').'.zip');
	header('Content-Length: '.strlen($output));
	header('Content-Transfer-Encoding: binary');
	echo $output;

	return;
}

function add_photos($checked, $type, $dir) {
	global $zipfile;

	if ($type == 'collections') {
		foreach ($checked as $cid) {
			$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."collections` WHERE `id`='".intval($cid)."'";
			$result = run_query($query);

			while ($row = mysql_fetch_assoc($result)) {
				$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."albums` WHERE `parent_id`='".$row['id']."'";
				$newresult = run_query($query);

				$newchecked = array();

				while ($newrow = mysql_fetch_assoc($newresult)) {
					$newchecked[] = $newrow['id'];
				}

				$newdir = $row['name'];

				$i = 1;

				while (is_dir($newdir)) {
					$newdir = $row['name'].'('.$i++.')';
				}

				// $zipfile -> add_dir($dir . $newdir);

				add_photos($newchecked, 'collection', $dir.$newdir.'/');
			}
		}
	} else if ($type == 'collection') {
		foreach ($checked as $aid) {
			$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."albums` WHERE `id`='".intval($aid)."'";
			$result = run_query($query);

			while ($row = mysql_fetch_assoc($result)) {
				$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE `parent_album`='".$row['id']."'";
				$newresult = run_query($query);

				$newchecked = array();

				while ($newrow = mysql_fetch_assoc($newresult)) {
					$newchecked[] = $newrow['id'];
				}

				$newdir = $row['name'];

				$i = 1;

				while (is_dir($newdir)) {
					$newdir = $row['name'].'('.$i++.')';
				}

				// $zipfile -> add_dir($dir . $newdir);

				add_photos($newchecked, 'album', $dir.$newdir.'/');
			}
		}
	} elseif ($type == 'album' || $type == 'search') {
		foreach ($checked as $pid) {
			$query = "SELECT * FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE `id`='".intval($pid)."'";
			$result = run_query($query);

			while ($row = mysql_fetch_assoc($result)) {
				$file_contents = file_get_contents('plog-content/images/'.$row['path'], true);
				$zipfile -> add_file($file_contents, $row['path']);

			}
		}
	}

	return;
}

?>