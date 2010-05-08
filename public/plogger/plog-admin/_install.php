<?php
if (is_file(dirname(dirname(__FILE__)).'/plog-config.php')) {
	require_once(dirname(dirname(__FILE__)).'/plog-config.php');
}
include_once(dirname(dirname(__FILE__)).'/plog-globals.php');
include_once(PLOGGER_DIR.'plog-includes/plog-functions.php');
include_once(PLOGGER_DIR.'plog-admin/includes/install-functions.php');
error_reporting(E_ALL);

// Set a session variable for session checks
$_SESSION['plogger_session'] = true;

// Serve the config file
if (!empty($_POST['dlconfig']) && !empty($_SESSION['plogger_config'])) {
	header('Content-type: application/octet-stream');
	header('Content-Disposition: attachment; filename="plog-config.php"');
	echo $_SESSION['plogger_config'];
	exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Plogger <?php echo plog_tr('Gallery | Install') ?></title>
	<meta http-equiv="Content-Type" content="txt/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="css/admin.css" />
</head>

<body>

<div><img src="images/plogger.gif" alt="Plogger" /></div>

<?php

// Check if Plogger is already installed
$installed = is_plogger_installed();

// If not installed, do the installation
if (!$installed) {
	// If not told to proceed, do the configuration setup
	if (empty($_POST['proceed'])) {
		$configured = do_install($_POST);
	}
	// If setup configuration done, do the install
	if (isset($_POST['proceed']) || $configured) {
		// If not DB information not defined, prompt the user to download the plog-config.php file
		if (!defined('PLOGGER_DB_HOST')) {
			echo "\n\t" . '<h1>'.plog_tr('Plogger Configuration Complete').'</h1>';
			echo "\n\n\t" . '<form action="_install.php" method="post">';
			echo "\n\n\t\t" . '<p>'.plog_tr('Configuration setup is now complete.').'</p>';
			echo "\n\n\t\t" . '<p>'.plog_tr('Click <strong>Install</strong> to complete the installation.').'</p>';
			if (!empty($_SESSION['plogger_config'])) {
				echo "\n\n\t\t" . '<p>'.sprintf(plog_tr('Before you can proceed, please %s to download configuration file for your gallery, then upload it to your webhost (into the same directory where you installed Plogger itself).'), '<input type="submit" class="submit-inline" name="dlconfig" value="'.plog_tr('click here').'" />').'</p>';
			}
			echo "\n\n\t\t" . '<p><input type="submit" class="submit" name="proceed" id="proceed" value="'.plog_tr('Install').'" /></p>';
				echo "\n\n\t" . '</form>'. "\n";
		// Otherwise, do the install
		} else {
			$errors = array();
			$mysql = check_mysql(PLOGGER_DB_HOST, PLOGGER_DB_USER, PLOGGER_DB_PW, PLOGGER_DB_NAME);
			if (empty($mysql)) {
				create_tables();
				configure_plogger($_SESSION['install_values']); // undefined index install_values
				include_once(PLOGGER_DIR.'plog-load-config.php');
				// If open permissions, have Plogger fix them
				if (isset($_SESSION['plogger_close_perms'])) {
					fix_open_perms($_SESSION['plogger_close_perms'], 'delete');
				}
				$col = add_collection(plog_tr('Plogger Test Collection'), plog_tr('Feel free to delete it'));
				// Only attempt to create an album if the collection was created - sloppy fix for multiple installs
				if (!empty($col['id'])) {
					$alb = add_album(plog_tr('Plogger Test Album'), plog_tr('Feel free to delete it'), $col['id']);
				}
			} else {
				echo plog_tr('There was an error with the MySQL connection').'!';
			}
			// If no errors, tell the user their login and password and link them to the login
			if (empty($errors)) {
				echo "\n\t" . '<h1>'.plog_tr('Plogger Install Complete').'</h1>';
				echo "\n\n\t" . '<p class="info width-700">'.plog_tr('You have successfully installed Plogger!').'<br /><br />';
				echo "\n\t" . sprintf(plog_tr('Your username is %s and your password is %s'), '<strong>'.$_SESSION['install_values']['admin_username'].'</strong>', '<strong>'.$_SESSION['install_values']['admin_password'].'</strong>');
				echo '</p>';
				if (is_open_perms(PLOGGER_DIR.'plog-content/')) {
					echo "\n\n\t" . '<p class="actions width-700">'.sprintf(plog_tr('You can now CHMOD the %s directory back to 0755'), '<strong>plog-content/</strong>').'.</p>';
				}
				echo "\n\n\t" . '<form action="index.php?r=plog-options.php" method="post">';
				echo "\n\t\t" . '<p><input class="submit" type="submit" name="login" value="'.plog_tr('Log In').'" /></p>';
				echo "\n\t" . '</form>'. "\n";
				unset($_SESSION['plogger_config']);
				unset($_SESSION['install_values']);
			} else {
				// Else display the errors
			}
		}
	}
} else {
	// Otherwise it's installed
	echo '<p>'.plog_tr('Plogger is already installed').'.</p>';
}

close_db();
close_ftp();
?>

</body>
</html>