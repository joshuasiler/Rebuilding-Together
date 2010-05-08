<?php
if (basename($_SERVER['PHP_SELF']) == basename( __FILE__ )) {
	// ignorance is bliss
	exit();
}

require_once(PLOGGER_DIR.'plog-admin/plog-admin-functions.php');

/**** Common Functions ****/

function maybe_add_column($table, $column, $add_sql) {
	$sql = "DESCRIBE $table";
	$res = mysql_query($sql);
	$found = false;
	while($row = mysql_fetch_array($res, MYSQL_NUM)) {
		if ($row[0] == $column) $found = true;
	}
	if (!$found) {
		mysql_query("ALTER TABLE $table ADD `$column` ".$add_sql);
		return plog_tr('Added new field to database').': '.$column;
	} else {
		if (defined('PLOGGER_DEBUG')) {
//			return plog_tr('Field').' <strong>'.$column.'</strong> .'plog_tr('already exists, ignoring.').'';
			return 'Field <strong>'.$column.'</strong> already exists, ignoring.';
		}
	}
}

function maybe_drop_column($table, $column) {
	$sql = "DESCRIBE $table";
	$res = mysql_query($sql);
	$found = false;
	while($row = mysql_fetch_array($res, MYSQL_NUM)) {
		if ($row[0] == $column) $found = true;
	}
	if ($found) {
		$sql = "ALTER TABLE $table DROP `$column`";
		mysql_query($sql);
		return plog_tr('Dropped column').': '.$column;
	} else {
		if (defined('PLOGGER_DEBUG')) {
//			return $column.' '.plog_tr('does not exist').'';
			return $column.' does not exist';
		}
	}
}

function maybe_add_table($table, $add_sql, $options = '') {
	$sql = "DESCRIBE $table";
	$res = mysql_query($sql);
	if (!$res) {
		$q = "CREATE table `$table` ($add_sql) $options";
		mysql_query($q);
		if (mysql_error()) {
			var_dump(mysql_error());
		} else {
			return true;
		}
	} else {
		if (defined('PLOGGER_DEBUG')) {
//			return plog_tr('Table').' <strong>'.$table.'</strong> .'plog_tr('already exists, ignoring.').'';
			return 'Table <strong>'.$table.'</strong> already exists, ignoring.';
		}
	}
}

function get_default_charset() {
	// Since 4.1 MySQL has support for specifying character encoding for tables
	// and I really want to use it if available. So we need figure out what version
	// we are running on and to the right thing
	$mysql_version = mysql_get_server_info();
	$mysql_charset_support = '4.1';
	$default_charset = '';

	if (1 == version_compare($mysql_version, $mysql_charset_support)) {
		$default_charset = 'DEFAULT CHARACTER SET UTF8';
	}
	return $default_charset;
}

function gd_missing() {
	require_once(PLOGGER_DIR.'/plog-includes/lib/phpthumb/phpthumb.functions.php');
	// This is copied over from phpthumb
	return phpthumb_functions::gd_version() < 1;
}

function check_requirements() {
	$errors = array();

	// Check that the session variable can be read
	if (!isset($_SESSION['plogger_session'])) {
		$save_path = ini_get('session.save_path');
		// Check that session.save_path is set (not set by default on PHP5)
		if (empty($save_path)) {
			if (!defined('SESSION_SAVE_PATH')) {
				$sample_text = ' ('.sprintf(plog_tr('see %s if your %s does not contain this variable'), 'plog-config-sample.php', 'plog-config.php').')';
			} else {
				$sample_text = '';
			}
			$errors[] = sprintf( plog_tr('The PHP %s variable is not set in your php.ini file.'), '<strong>session.save_path</strong>').' '.sprintf(plog_tr('You can attempt to set this by adding a writable directory path to the %s variable in %s or contact your webhost on how to set this system variable.'), '<strong>SESSION_SAVE_PATH</strong>', 'plog-config.php'.$sample_text);
		} else {
			$errors[] = sprintf(plog_tr('PHP session cookies are not being set. Please check that session cookies are enabled on your browser or verify that your %s variable is set up correctly.'), '<strong>session.save_path</strong>').' '.sprintf(plog_tr('You can attempt to set this by adding a writable directory path to the %s variable in %s or contact your webhost on how to set this system variable.'), '<strong>SESSION_SAVE_PATH</strong>', 'plog-config.php'.$sample_text);
		}
	}

	// Check that the GD library is available
	if (gd_missing()) {
		$errors[] = plog_tr('PHP GD module was not detected.');
	}

	// Check that MySQL functions are available
	if (!function_exists('mysql_connect')) {
		$errors[] = plog_tr('PHP MySQL module was not detected.');
	}

	// Make sure we have permission to read these folders/files
	$files_to_read = array('./plog-admin', './plog-admin/css', './plog-admin/images', './plog-content/images', './plog-content/thumbs', './plog-content/uploads', './plog-includes', './plog-includes/lib');
	foreach($files_to_read as $file) {
		if (!is_readable(PLOGGER_DIR.$file)) {
			$errors[] = sprintf(plog_tr('The path %s is not readable by the web server.'), '<strong>'.realpath(PLOGGER_DIR.$file).'</strong>');
		}
	}

	// Workaround for upgrading from beta1 since there are conflicting function in plog-functions.php and beta1 plog-connect.php
	if (function_exists('is_safe_mode')) {
		// If safe mode enabled, we will use the FTP workarounds to deal with folder permissions
		if (!is_safe_mode()) {
			// Make sure we have permission to write to these folders
			$files_to_write = array('./plog-content/images', './plog-content/thumbs');
			$i = 0;
			foreach($files_to_write as $file) {
				if (!is_writable(PLOGGER_DIR.$file)) {
					$errors[] = sprintf(plog_tr('The path %s is not writable by the web server.'), '<strong>'.realpath(PLOGGER_DIR.$file).'</strong>');
				} else if (is_open_perms(realpath(PLOGGER_DIR.$file))) {
					$_SESSION['plogger_close_perms'][basename($file)] = realpath(PLOGGER_DIR.$file);
				}
			}
			if (isset($_SESSION['plogger_close_perms'])) {
				if (!is_writable(PLOGGER_DIR.'plog-content/')) {
					$errors[] = sprintf(plog_tr('Please temporarily CHMOD the %s directory to 0777 to allow Plogger to create initial directories for increased security. You will be prompted to CHMOD the directory back to 0755 after installation is complete.'), '<strong>plog-content/</strong>');
				}
			}
		}
	}

	return $errors;
}

function check_mysql_form($form) {
	$errors = array();

	if (empty($form['db_host'])) {
		$errors[] = plog_tr('Please enter the name of your MySQL host.');
	}

	if (empty($form['db_user'])) {
		$errors[] = plog_tr('Please enter the MySQL username.');
	}

	if (empty($form['db_name'])) {
		$errors[] = plog_tr('Please enter the MySQL database name.');
	}

	return $errors;
}

function check_ftp_form($form) {
	$errors = array();

	if (empty($form['ftp_host'])) {
		$errors[] = plog_tr('Please enter the name of your FTP host.');
	}

	if (empty($form['ftp_user'])) {
		$errors[] = plog_tr('Please enter the FTP username.');
	}

	if (empty($form['ftp_pass'])) {
		$errors[] = plog_tr('Please enter the FTP password.');
	}

	if (!empty($form['ftp_path'])) {
		if (substr($form['ftp_path'], 0, 1) != '/'){
			$form['ftp_path'] = '/'.$form['ftp_path'];
		}
		if (substr($form['ftp_path'], -1) != '/'){
			$form['ftp_path'] = $form['ftp_path'].'/';
		}
	}

	return array('errors' => $errors, 'form' => $form);
}

function check_ftp($host, $user, $pass, $path) {
	$errors = array();

	$connection = @ftp_connect($host);
	if (!$connection) {
		$errors[] = sprintf(plog_tr('Cannot connect to FTP host %s. Please check your FTP Host:'), '<strong>'.$host.'</strong>');
	} else {
		$login = @ftp_login($connection, $user, $pass);
		if (!$login) {
			$errors[] = sprintf( plog_tr('Cannot login to FTP host %s with username %s and password %s. Please check your FTP Username: and FTP Password:'), '<strong>'.$host.'</strong>', '<strong>'.$user.'</strong>', '<strong>'.$pass.'</strong>');
		} else {
			$checkdir = @ftp_chdir($connection, $path.'plog-content/images/'); // Check to see if the plog-content/images/ folder is accessible
			if (!$checkdir) {
				$errors[] = sprintf(plog_tr('Cannot find the Plogger %s directory along the path %s. Please check your FTP path to Plogger base folder (from FTP login):'), '<strong>plog-content/images/</strong>', '<strong>'.$path.'</strong>');
			}
		}
	}
	@ftp_close($connection);
	return $errors;
}

/**** Install Functions ****/

function do_install($form) {
	$form = array_map('stripslashes', $form);
	$form = array_map('trim', $form);

	// First check the requirements
	$errors = check_requirements();
	if (sizeof($errors) > 0) {
		echo "\t" . '<p class="errors">'.plog_tr('Plogger cannot be installed until the following problems are resolved').':</p>';
		echo "\n\n\t\t" . '<ul class="info">';
		foreach($errors as $error) {
			echo "\n\t\t\t" . '<li class="margin-5">'.$error.'</li>';
		}
		echo "\n\t\t" . '</ul>';
		echo "\n\n\t" . '<form method="get" action="'.$_SERVER['REQUEST_URI'].'">
		<p><input class="submit" type="submit" value="'.plog_tr('Try again').'" /></p>
	</form>' . "\n";
		return false;
	}

	$ok = false;
	$errors = array();

	// If we've already defined the database information, pass the values and skip them on the form
	if (defined('PLOGGER_DB_HOST')) {
		$mysql = check_mysql(PLOGGER_DB_HOST, PLOGGER_DB_USER, PLOGGER_DB_PW, PLOGGER_DB_NAME);
		if (!empty($mysql)) {
			$mysql_fail = true;
		} else {
			unset($_SESSION['plogger_config']);
		}
		// Set the form values equal to config values if already set
		if (empty($form['db_host'])) {
			$form['db_host'] = PLOGGER_DB_HOST;
		}
		if (empty($form['db_user'])) {
			$form['db_user'] = PLOGGER_DB_USER;
		}
		if (empty($form['db_pass'])) {
			$form['db_pass'] = PLOGGER_DB_PW;
		}
		if (empty($form['db_name'])) {
			$form['db_name'] = PLOGGER_DB_NAME;
		}
	}

	if (isset($form['action']) && $form['action'] == 'install') {
		if (!defined('PLOGGER_DB_HOST') || isset($mysql_fail)) {
			$mysql_form_check = check_mysql_form($form);
			if (!empty($mysql_form_check)) {
				$errors = array_merge($errors, $mysql_form_check);
			}
		}

		if (empty($form['gallery_name'])) {
			$errors[] = plog_tr('Please enter the name for your gallery.');
		}

		if (empty($form['admin_email'])) {
			$errors[] = plog_tr('Please enter your email address.');
		}

		if (empty($form['admin_username'])) {
			$errors[] = plog_tr('Please enter a username.');
		}

		if (empty($form['admin_password'])) {
			$errors[] = plog_tr('Please enter a password.');
		}

		if ($form['admin_password'] != $form['admin_password_confirm']) {
			$errors[] = plog_tr('Your passwords do not match. Please try again.');
		}

		if (is_safe_mode()) {
			// If safe_mode enabled, check the FTP information form inputs
			$ftp_form_check = check_ftp_form($form);
			$form = $ftp_form_check['form'];
			if (!empty($ftp_form_check['form']['errors'])) {
				$errors = array_merge($errors, $ftp_form_check['form']['errors']);
			}
		}

		if (empty($errors)) {
			$mysql_errors = check_mysql($form['db_host'], $form['db_user'], $form['db_pass'], $form['db_name']);
			if (is_safe_mode()) {
				$ftp_errors = check_ftp($form['ftp_host'], $form['ftp_user'], $form['ftp_pass'], $form['ftp_path']);
			} else {
				$ftp_errors = array();
			}
			$errors = array_merge($mysql_errors, $ftp_errors);
			$ok = empty($errors);
		}

		if (!$ok) {
			echo '<ul class="errors" style="background-image: none;">' . "\n\t" . '<li class="margin-5">';
			echo join("</li>\n\t<li class=\"margin-5\">", $errors);
			echo "</li>\n</ul>\n\n";
		} else {
			$_SESSION['install_values'] = array(
				'gallery_name' => $form['gallery_name'],
				'admin_email' => $form['admin_email'],
				'admin_password' => $form['admin_password'],
				'admin_username' => $form['admin_username']
			);
			if (is_safe_mode()) {
				$_SESSION['ftp_values'] = array(
					'ftp_host' => $form['ftp_host'],
					'ftp_user' => $form['ftp_user'],
					'ftp_pass' => $form['ftp_pass'],
					'ftp_path' => $form['ftp_path']
				);
			}

			if (!defined('PLOGGER_DB_HOST') || isset($mysql_fail)) {
				// Serve the config file and ask user to upload it to webhost
				$_SESSION['plogger_config'] = create_config_file($form['db_host'], $form['db_user'], $form['db_pass'], $form['db_name']);
			}
			return true;
		}
	}

	include(PLOGGER_DIR.'plog-admin/includes/install-form-setup.php');
	return false;
}

function create_tables() {
	$default_charset = get_default_charset();


	maybe_add_table(
	PLOGGER_TABLE_PREFIX.'collections'
	,"`name` varchar(128) NOT NULL default '',
	`description` varchar(255) NOT NULL default '',
	`path` varchar(255) NOT NULL default '',
	`id` int(11) NOT NULL auto_increment,
	`thumbnail_id` int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)"
	,"Type=MyISAM $default_charset");

	maybe_add_table(
	PLOGGER_TABLE_PREFIX.'albums'
	," `name` varchar(128) NOT NULL default '',
	`id` int(11) NOT NULL auto_increment,
	`description` varchar(255) NOT NULL default '',
	`path` varchar(255) NOT NULL default '',
	`parent_id` int(11) NOT NULL default '0',
	`thumbnail_id` int(11) NOT NULL default '0',
	PRIMARY KEY (`id`),
	INDEX pid_idx (`parent_id`)"
	," Type=MyISAM $default_charset");

	maybe_add_table(
	PLOGGER_TABLE_PREFIX.'pictures'
	,"`path` varchar(255) NOT NULL default '',
	`parent_album` int(11) NOT NULL default '0',
	`parent_collection` int(11) NOT NULL default '0',
	`caption` mediumtext NOT NULL,
	`description` text NOT NULL,
	`id` int(11) NOT NULL auto_increment,
	`date_modified` timestamp(14) NOT NULL,
	`date_submitted` timestamp(14) NOT NULL,
	`EXIF_date_taken` varchar(64) NOT NULL default '',
	`EXIF_camera` varchar(64) NOT NULL default '',
	`EXIF_shutterspeed` varchar(64) NOT NULL default '',
	`EXIF_focallength` varchar(64) NOT NULL default '',
	`EXIF_flash` varchar(64) NOT NULL default '',
	`EXIF_aperture` varchar(64) NOT NULL default '',
	`EXIF_iso` varchar(64) NOT NULL default '',
	`allow_comments` int(11) NOT NULL default '1',
	PRIMARY KEY (`id`),
	INDEX pa_idx (`parent_album`),
	INDEX pc_idx (`parent_collection`)"
	,"Type=MyISAM $default_charset");

	maybe_add_table(
	PLOGGER_TABLE_PREFIX.'comments'
	,"`id` int(11) NOT NULL auto_increment,
	`parent_id` int(11) NOT NULL default '0',
	`author` varchar(64) NOT NULL default '',
	`email` varchar(64) NOT NULL default '',
	`url` varchar(64) NOT NULL default '',
	`date` datetime NOT NULL,
	`comment` longtext NOT NULL,
	`ip` char(64),
	`approved` tinyint default '1',
	PRIMARY KEY (`id`),
	INDEX pid_idx (`parent_id`),
	INDEX approved_idx (`approved`)"
	,"Type=MyISAM $default_charset");

	maybe_add_table(
	PLOGGER_TABLE_PREFIX.'config'
	,"`gallery_name` varchar(255) NOT NULL default '',
	`gallery_url` varchar(255) NOT NULL default '',
	`admin_username` varchar(64) NOT NULL default '',
	`admin_email` varchar(50) NOT NULL default '',
	`admin_password` varchar(64) NOT NULL default '',
	`activation_key` varchar(64) NOT NULL default '',
	`date_format` varchar(64) NOT NULL default '',
	`compression` int(11) NOT NULL default '75',
	`thumb_num` int(11) NOT NULL default '0',
	`default_sortby` varchar(20) NOT NULL default '',
	`default_sortdir` varchar(5) NOT NULL default '',
	`album_sortby` varchar(20) NOT NULL default '',
	`album_sortdir` varchar(5) NOT NULL default '',
	`collection_sortby` varchar(20) NOT NULL default '',
	`collection_sortdir` varchar(5) NOT NULL default '',
	`allow_dl` smallint(1) NOT NULL default '0',
	`allow_comments` smallint(1) NOT NULL default '1',
	`allow_print` smallint(1) NOT NULL default '1',
	`truncate` int(11) NOT NULL default '0',
	`feed_num_entries` int(15) NOT NULL default '15',
	`feed_title` text NOT NULL,
	`feed_content` tinyint NOT NULL default '1',
	`use_mod_rewrite` tinyint NOT NULL default '0',
	`comments_notify` tinyint NOT NULL default '1',
	`comments_moderate` tinyint NOT NULL default '0',
	`theme_dir` varchar(128) NOT NULL default '',
	`thumb_nav_range` int(11) NOT NULL default '0',
	`allow_fullpic` tinyint default '1',
	PRIMARY KEY (`thumb_num`)"
	,"Type=MyISAM $default_charset");

	maybe_add_table(
	PLOGGER_TABLE_PREFIX.'thumbnail_config'
	,"`id` int(10) unsigned NOT NULL auto_increment,
	`update_timestamp` int(10) unsigned default NULL,
	`max_size` int(10) unsigned default NULL,
	`disabled` tinyint default '0',
	`resize_option` tinyint default '2',
	PRIMARY KEY (`id`)"
	,"Type=MyISAM $default_charset");

	/*maybe_add_table(
	PLOGGER_TABLE_PREFIX.'tag2picture'
	,"`tag_id` bigint(20) unsigned NOT NULL default '0',
	`picture_id` bigint(20) unsigned NOT NULL default '0',
	`tagdate` datetime default NULL,
	KEY `tag_id` (`tag_id`),
	KEY `picture_id` (`picture_id`)"
	,"Type=MyISAM $default_charset");

	maybe_add_table(
	PLOGGER_TABLE_PREFIX.'tags'
	,"`id` bigint(20) unsigned NOT NULL auto_increment,
	`tag` char(50) NOT NULL default '',
	`tagdate` datetime NOT NULL default '0000-00-00 00:00:00',
	`urlified` char(50) NOT NULL default '',
	PRIMARY KEY  (`id`),
	UNIQUE `tag` (`tag`),
	UNIQUE `urlified` (`urlified`)"
	,"Type=MyISAM $default_charset");*/

}

function configure_plogger($form) {
	// Use a random timestamp from the past to keep the existing thumbnails
	$long_ago = 1096396500;

	$thumbnail_sizes = array(
		THUMB_SMALL => 100,
		THUMB_LARGE => 500,
		THUMB_RSS => 400,
		THUMB_NAV => 60
	);

	foreach($thumbnail_sizes as $key => $size) {
		$resize = ($key == THUMB_SMALL || $key == THUMB_NAV) ? 3: 2;
		$sql = "INSERT INTO `".PLOGGER_TABLE_PREFIX."thumbnail_config` (`id`, `update_timestamp`, `max_size`, `resize_option`)
		VALUES('$key', '$long_ago', '$size', '$resize')";
		mysql_query($sql);
	}

	$config['gallery_url'] = 'http://'.$_SERVER['SERVER_NAME'].dirname(dirname($_SERVER['PHP_SELF']));
	// Remove plog-admin/ from the end, if present .. is there a better way to determine the full url?
	if (strpos($config['gallery_url'], 'plog-admin/')) {
		$config['gallery_url'] = substr($config['gallery_url'], 0, strpos($config['gallery_url'], 'plog-admin/'));
	}
	// Verify that gallery URL contains a trailing slash. if not, add one.
	if ($config['gallery_url']{strlen($config['gallery_url'])-1} != '/') {
		$config['gallery_url'] .= '/';
	}
	// Verify that the gallery URL begins with 'http://' for mod_rewrite 301 redirects
	if (strpos($config['gallery_url'], 'http://') === false) {
		$config['gallery_url'] = 'http://'.$config['gallery_url'];
	}
	$config['admin_username'] = $form['admin_username'];
	$config['admin_password'] = $form['admin_password'];
	$config['admin_email'] = $form['admin_email'];
	$config['gallery_name'] = $form['gallery_name'];

	$config = array_map('mysql_real_escape_string', $config);

	$row_exist = mysql_query("SELECT * FROM `".PLOGGER_TABLE_PREFIX."config`");
	$row_exist_num = mysql_num_rows($row_exist);

	if ($row_exist_num == 0) {
		$query = "INSERT INTO `".PLOGGER_TABLE_PREFIX."config`
			(`theme_dir`,
			`compression`,
			`thumb_num`,
			`admin_username`,
			`admin_email`,
			`admin_password`,
			`date_format`,
			`feed_title`,
			`gallery_name`,
			`gallery_url`)
			VALUES
			('default',
			75,
			20,
			'${config['admin_username']}',
			'${config['admin_email']}',
			MD5('${config['admin_password']}'),
			'n.j.Y',
			'Plogger Photo Feed',
			'${config['gallery_name']}',
			'${config['gallery_url']}')";
	} else {
		$query = "UPDATE `".PLOGGER_TABLE_PREFIX."config` SET
			`theme_dir` = 'default',
			`compression` = 75,
			`thumb_num` = 20,
			`admin_username` = '${config['admin_username']}',
			`admin_email` = '${config['admin_email']}',
			`admin_password` = MD5('${config['admin_password']}'),
			`date_format` = 'n.j.Y',
			`feed_title` = 'Plogger Photo Feed',
			`gallery_name` = '${config['gallery_name']}',
			`gallery_url` = '${config['gallery_url']}'";
	}
	mysql_query($query);

	// Create the FTP columns in the config table if safe_mode enabled/
	if (is_safe_mode() && isset($_SESSION['ftp_values'])) {
		configure_ftp($_SESSION['ftp_values']);
	}

	// Send an email with the username and password
	$from = str_replace('www.', '', $_SERVER['HTTP_HOST']);
	ini_set('sendmail_from', 'noreply@'.$from); // Set for Windows machines
	@mail(
		$config['admin_email'],
		plog_tr('[Plogger] Your new gallery'),
		plog_tr('You have successfully installed your new Plogger gallery.') . "\n\n" .sprintf(plog_tr('You can log in and manage it at %s'), $config['gallery_url'].'plog-admin/') . "\n\n" .plog_tr('Username').': '.$config['admin_username']. "\n" .plog_tr('Password').': '.$config['admin_password'],
		'From: Plogger <noreply@'.$from.'>'
	);
}

function configure_ftp($form) {
	maybe_add_column(PLOGGER_TABLE_PREFIX.'config', 'ftp_host', "varchar(64) NOT NULL default ''");
	maybe_add_column(PLOGGER_TABLE_PREFIX.'config', 'ftp_user', "varchar(64) NOT NULL default ''");
	maybe_add_column(PLOGGER_TABLE_PREFIX.'config', 'ftp_pass', "varchar(64) NOT NULL default ''");
	maybe_add_column(PLOGGER_TABLE_PREFIX.'config', 'ftp_path', "varchar(255) NOT NULL default ''");
	$query = "UPDATE `".PLOGGER_TABLE_PREFIX."config` SET
		`ftp_host` = '".mysql_real_escape_string($form['ftp_host'])."',
		`ftp_user` = '".mysql_real_escape_string($form['ftp_user'])."',
		`ftp_pass` = '".mysql_real_escape_string($form['ftp_pass'])."',
		`ftp_path` = '".mysql_real_escape_string($form['ftp_path'])."'";
	mysql_query($query);
}

function fix_open_perms($dirs, $action = 'rename') {
	if (!empty($dirs)) {
		foreach ($dirs as $key => $dir) {
			if ($action == 'delete') {
				kill_dir(PLOGGER_DIR.'plog-content/'.$key);
			} else {
				@rename(PLOGGER_DIR.'plog-content/'.$key, PLOGGER_DIR.'plog-content/'.$key.'-old');
			}
			makeDirs(PLOGGER_DIR.'plog-content/'.$key);
		}
	}
}

function create_config_file($db_host, $db_user, $db_pass, $db_name) {
	$cfg_file = "<?php\n";
	$cfg_file .= "/* You can manually modify this file before installing (renaming this file to plog-config.php before\n";
	$cfg_file .= " * installation) or you can let Plogger generate the file automatically by running the installation script\n";
	$cfg_file .= " * (run plog-admin/_install.php in your browser).\n\n";
	$cfg_file .= " * If you want to change the database connection information, you may also edit this file manually\n";
	$cfg_file .= " * after Plogger has been installed. */\n\n";
	$cfg_file .= "/* MySQL hostname */\n";
	$cfg_file .= "define('PLOGGER_DB_HOST', '".$db_host."');\n\n";
	$cfg_file .= "/* MySQL database username */\n";
	$cfg_file .= "define('PLOGGER_DB_USER', '".$db_user."');\n\n";
	$cfg_file .= "/* MySQL database password */\n";
	$cfg_file .= "define('PLOGGER_DB_PW', '".addcslashes($db_pass, "\\'")."');\n\n"; // Escape certain password characters stored in single quotes (\) (')
	$cfg_file .= "/* The name of the database for Plogger */\n";
	$cfg_file .= "define('PLOGGER_DB_NAME', '".$db_name."');\n\n";
	$cfg_file .= "/* Define the Plogger database table prefix. You can have multiple installations in one database if you give\n";
	$cfg_file .= " * each a unique prefix. Only numbers, letters, and underscores are permitted (i.e., plogger_). */\n";
	$cfg_file .= "define('PLOGGER_TABLE_PREFIX', 'plogger_');\n\n";
	$cfg_file .= "/* Define the Plogger directory permissions. Change permissions if you are having issues with images or\n";
	$cfg_file .= " * sub-directories being saved, moved, or deleted from the Plogger-created directories (i.e. Collections\n";
	$cfg_file .= " * or Albums) */\n";
	$cfg_file .= "define('PLOGGER_CHMOD_DIR', 0755);\n\n";
	$cfg_file .= "/* Define the Plogger file permissions. Change permissions if you are having issues with viewing,\n";
	$cfg_file .= " * deleting, or moving images within Plogger (i.e. Pictures) */\n";
	$cfg_file .= "define('PLOGGER_CHMOD_FILE', 0644);\n\n";
	$cfg_file .= "/* Is Plogger embedded in another program, like WordPress?\n";
	$cfg_file .= " * 1/0 (True/False) if set will overrule automatic check */\n";
	$cfg_file .= "define('PLOGGER_EMBEDDED', '');\n\n";
	$cfg_file .= "/* Define a directory path to save session variables if you are having trouble logging in or Plogger is\n";
	$cfg_file .= " * telling you that you have session.save_path issues and/or if your server php.ini setup has a\n";
	$cfg_file .= " * blank session.save_path php.ini variable */\n";
	$cfg_file .= "define('PLOGGER_SESSION_SAVE_PATH', '');\n\n";
	$cfg_file .= "/* Plogger localized language, defaults to English. Change this to localize Plogger.\n";
	$cfg_file .= " * A corresponding MO file for the chosen language must be installed in /plog-content/translations/.\n";
	$cfg_file .= " * For example, upload de.mo to /plog-content/translations/ and set PLOGGER_LOCALE to 'de' to\n";
	$cfg_file .= " * enable German language support.\n";
	$cfg_file .= " * Example language codes: da, de, et, fr, pl, ro, tr, en-CA (for Canadian English) */\n";
	$cfg_file .= "define('PLOGGER_LOCALE', '');\n\n";
	$cfg_file .= "/* Turn on debug mode if trying to troubleshoot issues.\n";
	$cfg_file .= " * 1/0 (True/False) if set will display debug messages at bottom of gallery and admin pages\n";
	$cfg_file .= " * Do not leave this running if gallery is functioning properly. */\n";
	$cfg_file .= "define('PLOGGER_DEBUG', '');\n\n";
	$cfg_file .= "?>";
	return $cfg_file;
}

/**** Upgrade Functions ****/

function upgrade_database() {
	global $config, $thumbnail_config;
	$default_charset = get_default_charset();
	$output = array();

/** plogger_thumbnail_config **/
	$thumb_table = maybe_add_table(
		PLOGGER_TABLE_PREFIX.'thumbnail_config'
		,"`id` int(10) unsigned NOT NULL auto_increment,
		`update_timestamp` int(10) unsigned default NULL,
		`max_size` int(10) unsigned default NULL,
		`disabled` tinyint default 0,
		PRIMARY KEY (`id`)"
		);

	if ($thumb_table === true) {
		$output[] = plog_tr('Added new table').': '.PLOGGER_TABLE_PREFIX.'thumbnail_config';
		// Use a random timestamp from the past to keep the existing thumbnails
		$long_ago = 1096396500;

		if (!isset($config['max_thumbnail_size'])) {
			$config['max_thumbnail_size'] = 100;
		}
		if (!isset($thumbnail_config[THUMB_SMALL]) || empty($thumbnail_config[THUMB_SMALL]['size'])) {
			$sql = "INSERT INTO `".PLOGGER_TABLE_PREFIX."thumbnail_config` (id, update_timestamp, max_size)
				VALUES('".THUMB_SMALL."', '".$long_ago."', '".$config['max_thumbnail_size']."')";
			mysql_query($sql);
		}

		if (!isset($config['max_display_size'])) {
			$config['max_display_size'] = 500;
		}
		if (!isset($thumbnail_config[THUMB_LARGE]) || empty($thumbnail_config[THUMB_LARGE]['size'])) {
			$sql = "INSERT INTO `".PLOGGER_TABLE_PREFIX."thumbnail_config` (id, update_timestamp, max_size)
				VALUES('".THUMB_LARGE."', '".$long_ago."', '".$config['max_display_size']."')";
			mysql_query($sql);
		}

		if (!isset($config['rss_thumbsize'])) {
			$config['rss_thumbsize'] = 400;
		}
		if (!isset($thumbnail_config[THUMB_RSS]) || empty($thumbnail_config[THUMB_RSS]['size'])) {
			$sql = "INSERT INTO `".PLOGGER_TABLE_PREFIX."thumbnail_config` (id, update_timestamp, max_size)
				VALUES('".THUMB_RSS."', '".$long_ago."', '".$config['rss_thumbsize']."')";
			mysql_query($sql);
		}

		if (!isset($config['nav_thumbsize'])) {
			$config['nav_thumbsize'] = 60;
		}
		if (!isset($thumbnail_config[THUMB_NAV]) || empty($thumbnail_config[THUMB_NAV]['size'])) {
			$sql = "INSERT INTO `".PLOGGER_TABLE_PREFIX."thumbnail_config` (id, update_timestamp, max_size)
				VALUES('".THUMB_NAV."', '".$long_ago."', '".$config['nav_thumbsize']."')";
			mysql_query($sql);
		}
	}

	$thumbnail_add_list = array(
		'disabled' => "tinyint default 0",
		'resize_option' => "tinyint default 2"
	);
	foreach ($thumbnail_add_list as $key => $value) {
		$result = maybe_add_column(PLOGGER_TABLE_PREFIX.'thumbnail_config', $key, $value);
		if (!empty($result)) {
			$output[] = $result;
		}
	}

	// Make sure to set the resize_option to square for small thumbs if previously set
	if (isset($config['square_thumbs']) && $config['square_thumbs'] == 1) {
		$sql = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config` SET `resize_option` = '3' WHERE `id` = '".THUMB_SMALL."'";
		mysql_query($sql);
	}

	// Move enable_thumb_nav setting to plogger_thumbnail_config table
	if (isset($config['enable_thumb_nav'])) {
		$disabled = ($config['enable_thumb_nav'] == 0) ? 1 : 0;
		$sql = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config` SET `disabled` = '$disabled' WHERE `id` = '".THUMB_NAV."'";
		mysql_query($sql);
	}

	// set navigation thumbnails to square
	$sql = "UPDATE `".PLOGGER_TABLE_PREFIX."thumbnail_config` SET `resize_option` = '3' WHERE `id` = '".THUMB_NAV."'";
	mysql_query($sql);
	

/** plogger_config **/
	$config_drop_list = array(
		'max_thumbnail_size',
		'max_display_size',
		'rss_thumbsize',
		'feed_language',
		'enable_thumb_nav',
		'square_thumbs'
	);
	foreach ($config_drop_list as $value) {
		$result = maybe_drop_column(PLOGGER_TABLE_PREFIX.'config', $value);
		if (!empty($result)) {
			$output[] = $result;
		}
	}

	$config_add_list = array(
		'gallery_url' => "varchar(255) NOT NULL",
		// RSS config
		'feed_num_entries' => "int(15) NOT NULL default '15'",
		'feed_title' => "varchar(255) NOT NULL default 'Plogger Photo Feed'",
		'feed_content' => "tinyint default '1'",
		// Cruft-free URLs
		'use_mod_rewrite' => "smallint NOT NULL default '0'",
		// Default sort order
		'default_sortdir' => "varchar(5) NOT NULL",
		'default_sortby' => "varchar(20) NOT NULL",
		// Add field for admin email
		'admin_email' => "varchar(50) NOT NULL",
		// Disable link to full size pic
		'allow_fullpic' => "tinyint NOT NULL default '1'",
		// Comment notify
		'comments_notify' => "tinyint NOT NULL",
		// Comment moderation
		'comments_moderate' => "tinyint NOT NULL default 0",
		// User definable theme directory
		'theme_dir' => "varchar(128) NOT NULL",
		// Add support for user defined sort order for albums and collections
		'album_sortby' => "varchar(20) NOT NULL default 'id'",
		'album_sortdir' => "varchar(5) NOT NULL default 'DESC'",
		'collection_sortby' => "varchar(20) NOT NULL default 'id'",
		'collection_sortdir' => "varchar(5) NOT NULL default 'DESC'",
		// Add support for thumbnail configuration
		'thumb_nav_range' => "int(11) NOT NULL default 0",
		// Add reset password activation key
		'activation_key' => "varchar(64) NOT NULL default ''"
	);
	foreach ($config_add_list as $key => $value) {
		$result = maybe_add_column(PLOGGER_TABLE_PREFIX.'config', $key, $value);
		if (!empty($result)) {
			$output[] = $result;
		}
	}

	// Insert the gallery_url if not already set
	if (!isset($config['gallery_url']) || empty($config['gallery_url'])) {
		$config['baseurl'] = 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['PHP_SELF'])).'/';
		$output[] = plog_tr('Setting gallery url to ').$config['baseurl'];
		$sql = "UPDATE `".PLOGGER_TABLE_PREFIX."config` SET gallery_url = '".$config['baseurl']."'";
		mysql_query($sql);
	}

	// Insert default theme directory if not already set
	if (!isset($config['theme_dir']) || empty($config['theme_dir'])) {
		$output[] = plog_tr('Setting default theme directory to \'default\'');
		$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."config SET `theme_dir` = 'default' WHERE 1";
		mysql_query($sql);
	}

/** plogger_collections **/
	$collections_add_list = array(
		// Selectable thumbnails
		'thumbnail_id' => "int(11) NOT NULL default 0",
		// Add the path column
		'path' => "varchar(255) NOT NULL"
	);
	foreach ($collections_add_list as $key => $value) {
		$result = maybe_add_column(PLOGGER_TABLE_PREFIX.'collections', $key, $value);
		if (!empty($result)) {
			$output[] = $result;
		}
	}

/** plogger_albums **/
	$albums_add_list = array(
		// Selectable thumbnails
		'thumbnail_id' => "int(11) NOT NULL default 0",
		// Add the path column
		'path' => "varchar(255) NOT NULL"
	);
	foreach ($albums_add_list as $key => $value) {
		$result = maybe_add_column(PLOGGER_TABLE_PREFIX.'albums', $key, $value);
		if (!empty($result)) {
			$output[] = $result;
		}
	}

/** plogger_pictures **/
	$pictures_add_list = array(
		// Add description
		'description' => "text",
		'EXIF_iso' => "varchar(64) NOT NULL default ''"
	);
	foreach ($pictures_add_list as $key => $value) {
		$result = maybe_add_column(PLOGGER_TABLE_PREFIX.'pictures', $key, $value);
		if (!empty($result)) {
			$output[] = $result;
		}
	}

/** plogger_comments **/
	$comments_add_list = array(
		// Add ip and approved fields to comments table
		'ip' => "char(64)",
		'approved' => "tinyint default 1"
	);
	foreach ($comments_add_list as $key => $value) {
		$result = maybe_add_column(PLOGGER_TABLE_PREFIX.'comments', $key, $value);
		if (!empty($result)) {
			$output[] = $result;
		}
	}

		/*$output[] = maybe_add_table(PLOGGER_TABLE_PREFIX.'tag2picture',"
		`tag_id` bigint(20) unsigned NOT NULL default '0',
		`picture_id` bigint(20) unsigned NOT NULL default '0',
		`tagdate` datetime default NULL,
		KEY `tag_id` (`tag_id`),
		KEY `picture_id` (`picture_id`)
	");

	$output[] = maybe_add_table(PLOGGER_TABLE_PREFIX.'tags',"
		`id` bigint(20) unsigned NOT NULL auto_increment,
		`tag` char(50) NOT NULL default '',
		`tagdate` datetime NOT NULL default '0000-00-00 00:00:00',
		`urlified` char(50) NOT NULL default '',
		PRIMARY KEY  (`id`),
		UNIQUE `tag` (`tag`),
		UNIQUE `urlified` (`urlified`)
	");*/

	$sql = 'ALTER TABLE '.PLOGGER_TABLE_PREFIX.'comments ADD INDEX approved_idx (`approved`)';
	mysql_query($sql);

	// Add ip and approved fields to comments table
	$sql = 'ALTER TABLE '.PLOGGER_TABLE_PREFIX.'comments CHANGE `date` `date` datetime';
	mysql_query($sql);

	// Convert charsets
	// Since 4.1 MySQL has support for specifying character encoding for tables
	// and I really want to use it if available. So we need figure out what version
	// we are running on and to the right hting
	$mysql_version = mysql_get_server_info();
	$mysql_charset_support = '4.1';
	$default_charset = '';

	if (1 == version_compare($mysql_version,$mysql_charset_support)) {
		$charset = 'utf8';
		$tables = array('collections', 'albums', 'pictures', 'comments', 'config', 'thumbnail_config');
		foreach($tables as $table) {
			$tablename = PLOGGER_TABLE_PREFIX.$table;
			$sql = "ALTER TABLE $tablename DEFAULT CHARACTER SET $charset";
			if (!mysql_query($sql)) {
				$output[] = "failed to convert $tablename to $charset<br />".mysql_error();
			}
		}
	}

	return $output;
}

function upgrade_image_list() {
	$list = array();
	$total = 0;

	// Strip 'images/' prefix from pictures table
	$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."pictures SET path = SUBSTRING(path,8) WHERE SUBSTRING(path,1,7) = 'images/'"; 
	mysql_query($sql);

	// Update 'path' for collections table
	$sql = "SELECT id,name FROM ".PLOGGER_TABLE_PREFIX."collections";
	$result = mysql_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."collections SET path = '".strtolower(sanitize_filename($row['name']))."' WHERE id = ".$row['id'];
		mysql_query($sql);
		if (!file_exists(PLOGGER_DIR.'plog-content/images/'.strtolower(sanitize_filename($row['name'])))) {
			$list[$total] = array('container' => 1, 'new_path' => 'plog-content/images/'.strtolower(sanitize_filename($row['name'])));
			$total++;
		}
	}

	// Update 'path' for albums table
	$sql = "SELECT a.id AS id, a.name AS name, c.path AS collection_path
					FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."collections c
					WHERE a.parent_id = c.id";
	$result = mysql_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."albums SET path = '".strtolower(sanitize_filename($row['name']))."' WHERE id = ".$row['id'];
		mysql_query($sql);
		if (!file_exists(PLOGGER_DIR.'plog-content/images/'.$row['collection_path'].'/'.strtolower(sanitize_filename($row['name'])))) {
			$list[$total] = array('container' => 1, 'new_path' => 'plog-content/images/'.$row['collection_path'].'/'.strtolower(sanitize_filename($row['name'])));
			$total++;
		}
	}

	// Loop through each image from the pictures table, get its parent album name and parent collection
	$sql = "SELECT p.path AS path, p.id AS pid,c.path AS collection_path, a.path AS album_path
			FROM ".PLOGGER_TABLE_PREFIX."albums a, ".PLOGGER_TABLE_PREFIX."pictures p, ".PLOGGER_TABLE_PREFIX."collections c 
			WHERE p.parent_album = a.id AND p.parent_collection = c.id";
	$result = mysql_query($sql);

	while($row = mysql_fetch_assoc($result)) {
		$filename = sanitize_filename(basename($row['path']));
		$c_directory = $row['collection_path'].'/';
		$a_directory = $row['collection_path'].'/'.$row['album_path'].'/';
		$new_path = $row['collection_path'].'/'.$row['album_path'].'/'.$filename;
		// If the file exists, grab the information and add to the total
		if (!file_exists(PLOGGER_DIR.'plog-content/images/'.$new_path)) {
			// First see if it's in the old directory structure
			if (file_exists(PLOGGER_DIR.'images/'.$row['path'])) {
				$path = 'images/';
			// Next check the temporary folder location for closing folder permissions
			} else if (file_exists(PLOGGER_DIR.'plog-content/images-old/'.$row['path'])) {
				$path = 'plog-content/images-old/';
			// Otherwise check if it's in the new structure, but set up without new sanitized paths
			} else if (file_exists(PLOGGER_DIR.'plog-content/images/'.$row['path'])) {
				$path = 'plog-content/images/';
			} else {
				// Have no idea where the old image is
				$path = '';
			}
			$list[$total] = array('id' => $row['pid'], 'old_path' => $path.$row['path'], 'new_path' => $new_path);
			$total++;
		}
	}

	// Add any photos from the uploads directory
	if (file_exists(PLOGGER_DIR.'uploads/')) {
		$old_uploads = get_files(PLOGGER_DIR.'uploads/', false, false, dirname(dirname(dirname(__FILE__))).'/uploads/');
		$new_uploads = get_files(PLOGGER_DIR.'plog-content/uploads/', false, false, dirname(dirname(dirname(__FILE__))).'/plog-content/uploads/');

		// Compare the two paths for differences
		$compare_uploads = array_diff($old_uploads, $new_uploads);
		foreach ($compare_uploads as $uploads) {
			$list[$total] = array('uploads' => 1, 'old_path' => 'uploads/'.$uploads, 'new_path' => 'plog-content/uploads/'.$uploads);
			$total++;
		}
	}

	$list['total'] = $total;
	return $list;
}

function upgrade_images($num, $list) {
	$output = array();
	$errors = array();
	$count = 0;

	$list = array_slice($list, 0, $num);

	foreach ($list as $image) {
		if (!empty($image['id'])) {
			// Work on the images - move physical file, create directory if necessary and update path in database
			if (!makeDirs(PLOGGER_DIR.'plog-content/images/'.dirname($image['new_path'].'/'))) {
				$errors[] = plog_tr('Could not create directory').': '.PLOGGER_DIR.'plog-content/images/'.$image['new_path'];
			} else {
				if (!move_this(PLOGGER_DIR.$image['old_path'], PLOGGER_DIR.'plog-content/images/'.$image['new_path'])) {
					$errors[] = plog_tr('Could not move file').': '.PLOGGER_DIR.$image['old_path']; 
				} else {
					@chmod(PLOGGER_DIR.$new_path, PLOGGER_CHMOD_DIR);
					$output[] = sprintf(plog_tr('Moved file %s -> %s'), '<strong>'.$image['old_path'].'</strong>', '<strong>'.'plog-content/images/'.$image['new_path'].'</strong>');
					// Update database
					$sql = "UPDATE ".PLOGGER_TABLE_PREFIX."pictures SET path = '".mysql_real_escape_string($image['new_path'])."' WHERE id = '".$image['id']."'";
					run_query($sql);
					// Generate a new small thumbnail after database has been updated in case script times out
					$thumbpath = generate_thumb($image['new_path'], $image['id'], THUMB_SMALL);
					$count++;
				}
			}
		} else if (!empty($image['uploads'])) {
			// Work on the uploads - move physical file and create directory in the uploads folder if necessary and update path in database
			if (!makeDirs(PLOGGER_DIR.dirname($image['new_path'].'/'))) {
				$errors[] = plog_tr('Could not create directory').': '.PLOGGER_DIR.$image['new_path'];
			} else {
				if (!move_this(PLOGGER_DIR.$image['old_path'], PLOGGER_DIR.$image['new_path'])) {
					$errors[] = plog_tr('Could not move file').': '.PLOGGER_DIR.$image['old_path']; 
				} else {
					@chmod(PLOGGER_DIR.$new_path, PLOGGER_CHMOD_DIR);
					$output[] = sprintf(plog_tr('Moved file %s -> %s'), '<strong>'.$image['old_path'].'</strong>', '<strong>'.$image['new_path'].'</strong>');
					$count++;
				}
			}
		} else if (!empty($image['container'])) {
			// Create the collection and album directory structure
			if (!makeDirs(PLOGGER_DIR.$image['new_path'].'/')) {
				$errors[] = plog_tr('Could not create directory').': '.PLOGGER_DIR.$image['new_path'];
			} else {
				$output[] = sprintf(plog_tr('Created directory %s'), '<strong>'.$image['new_path'].'</strong>');
				$count++;
			}
		}
	}

	return array('errors' => $errors, 'output' => $output, 'count' => $count);
}

function check_list() {
	$themes = array();
	$translations = array();

	// See if there are any old themes
	if (file_exists(PLOGGER_DIR.'themes/')) {
		$themes_old = get_files(PLOGGER_DIR.'themes/', true, false, dirname(dirname(dirname(__FILE__))).'/themes/');
		if (!empty($themes_old)) {
			foreach ($themes_old as $theme) {
				if (!empty($theme) && $theme != 'index.php') {
					$theme_parts = explode('/', $theme);
					$themes[] = $theme_parts[0].'/';
				}
			}
			$themes = array_unique($themes);
		}
	}

	// See if there are any old translations
	if (file_exists(PLOGGER_DIR.'plog-translations/')) {
		$translations_old = get_files(PLOGGER_DIR.'plog-translations/', true, false, dirname(dirname(dirname(__FILE__))).'/plog-translations/');
		if (!empty($translations_old)) {
			foreach ($translations_old as $trans) {
				if (!empty($trans)) {
					$translations[] = $trans;
				}
			}
			$translations = array_unique($translations);
		}
	}

	return array('themes' => $themes, 'translations' => $translations);
}

function cleanup_list() {
	$files = array();
	$folders = array();

	$file_list = array(
		'_install.php',
		'_upgrade.php',
		'plog-captcha.php',
		'plog-connect.php',
		'plog-functions.php',
		'plog-load_config.php',
		'plog-tag-functions.php',
		'set_session_var.php',
		'dynamics.js',
		'slideshow.js',
		'captcha.ttf',
		'plog-includes/plog-comment.php',
		'plog-includes/plog-tag-functions.php'
	);
	foreach ($file_list as $file) {
		if (file_exists(PLOGGER_DIR.$file)) {
			$files[] = PLOGGER_DIR.$file;
		}
	}

	$folder_list = array(
		'admin/',
		'css/',
		'graphics/',
		'images/',
		'lib/',
		'plog-translations/',
		'themes/',
		'thumbs/',
		'uploads/',
		'summary/',
		'plog-content/images-old/',
		'plog-content/thumbs-old/'
	);
	foreach ($folder_list as $folder) {
		if (file_exists(PLOGGER_DIR.$folder)) {
			$folders[] = PLOGGER_DIR.$folder;
		}
	}

	return array('files' => $files, 'folders' => $folders);
}

function cleanup_files($files, $folders) {
	global $config;
	$output = array();
	$errors = array();

	// Delete the files first
	foreach ($files as $file) {
		if (file_exists($file)) {
			if (kill_file($file)) {
				$output[] = plog_tr('Plogger found and deleted the file').': '.$file;
			} else {
				$errors[] = plog_tr('Plogger could not delete the file').': '.$file;
			}
		}
	}

	// Remove the folders since there should be no files in them
	foreach ($folders as $folder) {
		if (file_exists($folder)) {
			if (kill_dir($folder)) {
				$output[] = plog_tr('Plogger found and deleted the folder').': '.$folder;
			} else {
				$errors[] = plog_tr('Plogger could not delete the folder').': '.$folder;
			}
		}
	}

	return array('errors' => $errors, 'output' => $output);
}

?>