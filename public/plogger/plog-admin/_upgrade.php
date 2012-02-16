<?php
if (is_file(dirname(dirname(__FILE__)).'/plog-config.php')) {
	require_once(dirname(dirname(__FILE__)).'/plog-config.php');
}
include(dirname(dirname(__FILE__)).'/plog-globals.php');
include(PLOGGER_DIR.'plog-admin/includes/install-functions.php');
error_reporting(E_ALL);

// Set up some initial variables
$beta1 = false;
$needs_ftp = false;
$step = (isset($_GET['step'])) ? intval($_GET['step']) : 0;
// Set a session variable for session checks
$_SESSION['plogger_session'] = true;

// Serve the config file if prompted
if (!empty($_POST['dlconfig']) && !empty($_SESSION['plogger_config'])) {
	header('Content-type: application/octet-stream');
	header('Content-Disposition: attachment; filename="plog-config.php"');
	echo $_SESSION['plogger_config'];
	exit();
}

// Check if upgrading from 1.0beta1 which requires additional updates
if (file_exists(PLOGGER_DIR.'plog-connect.php') && !defined('PLOGGER_DB_HOST')) {
	$beta1 = true;
}

// If we are upgrading from 1.0beta1, we do not yet have a valid plog-config.php file
if (!$beta1) {
	// Now we can include the functions - conflict with run_query function in beta 1's plog-connect.php file
	include(PLOGGER_DIR.'plog-includes/plog-functions.php');
	// Make sure Plogger is installed first
	if (!is_plogger_installed()) {
		// If Plogger does not seem to be installed, redirect to _install.php
		header('Location: _install.php');
	} else {
		// If installed, check for safe_mode and if enabled, check for FTP workaround
		if (is_safe_mode()) {
			// Set up the FTP workaround information if prompted
			if (isset($_POST['ftp_host'])) {
				$ftp_errors = array();
				$form = array_map('stripslashes',$_POST);
				$form = array_map('trim',$_POST);

				// Check the form input values
				$ftp_form_check = check_ftp_form($form);
				$form = $ftp_form_check['form'];
				if (!empty($ftp_form_check['form']['errors'])) {
					$ftp_errors = $ftp_form_check['form']['errors'];
				}
				// If no ftp errors so far, check the ftp information
				if (empty($ftp_errors)) {
					$ftp_check = check_ftp($form['ftp_host'], $form['ftp_user'], $form['ftp_pass'], $form['ftp_path']);
				}
				// If still no ftp errors, add the information to the database
				if (empty($ftp_check)) {
					configure_ftp($form);
					$config['ftp_host'] = $form['ftp_host'];
					$config['ftp_user'] = $form['ftp_user'];
					$config['ftp_pass'] = $form['ftp_pass'];
					$config['ftp_path'] = $form['ftp_path'];
				} else {
					// Otherwise, set up the errors for display later
					$ftp_errors = $ftp_check;
				}
			}
			if (!isset($config['ftp_host'])) {
					// Do we need an upgrade for people who used SVN only code that stored
					// FTP workaround data in plog-config.php?
					$needs_ftp = true;
			}
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Upgrade Plogger</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="css/admin.css" />
</head>

<body>

<div><img src="images/plogger.gif" alt="Plogger" /></div>
<?php
// Check the requirements
$errors = check_requirements();
if (sizeof($errors) > 0) {
	echo "\n\t" . '<p class="errors">'.plog_tr('Plogger cannot be upgraded until the following problems are resolved').':</p>';
	echo "\n\n\t\t" . '<ul class="info">';
	foreach($errors as $error) {
		echo "\n\t\t\t" . '<li class="margin-5">'.$error.'</li>';
	}
	echo "\n\t\t" . '</ul>';
	echo "\n\n\t\t" . '<form method="get" action="'.$_SERVER['REQUEST_URI'].'">
		<p><input class="submit" type="submit" value="'.plog_tr('Try again').'" /></p>
		</form>' . "\n";
} else { // End of requirement check
	$errors = "";

	echo "\n" . '<h1>'.plog_tr('Upgrading Plogger').'</h1>';

	switch ($step) {
		// Step 0 - gather any information needed
		case 0:
			if ($beta1) {
				// Include the old sql database info and create a new plog-config.php file with it
				include_once(PLOGGER_DIR.'plog-connect.php');
				$conf = create_config_file($DB_HOST, $DB_USER, $DB_PW, $DB_NAME);
				// Serve the config file and ask user to upload it to webhost
				$_SESSION['plogger_config'] = $conf;
				echo "\n\n\t" . '<h2 class="upgrade">'.plog_tr('Updating Configuration').'</h2>';
				echo "\n\n\t\t" . '<p>'.plog_tr('It appears you are updating from Plogger 1.0beta1. Your configuration file needs to be updated.').'</p>';
				echo "\n\n\t\t" . '<form action="_upgrade.php" method="post">';
				echo "\n\n\t\t" . '<p>'.sprintf(plog_tr('Before you can proceed, please %s to download the configuration file for your gallery, then upload it to your webhost (into the same directory where you installed Plogger itself).'), '<input class="submit-inline" type="submit" name="dlconfig" value=" '.plog_tr('click here').'" />') . '</p>';
				echo "\n\n\t\t" . '<p><input class="submit" type="submit" name="continue" id="continue" value=" '.plog_tr('Continue').'..." /></p>';
				echo "\n\n\t\t" . '</form>' . "\n";
				break;
			} else if ($needs_ftp) {
				// If we need to collect ftp information for safe_mode workaround
				// Handle errors and include the information form
				if (!empty($ftp_errors)) {
					echo "\n\n\t\t" . '<ul class="errors" style="background-image: none;">';
					foreach ($ftp_errors as $value) {
						echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
					}
					echo "\n\t\t" . '</ul>';
				}
				include(PLOGGER_DIR.'plog-admin/includes/install-form-setup.php');
				break;
			}

		// Step 1 - update the database
		case 1:
			$return = upgrade_database();
			if (!empty($return)) {
				echo "\n\n\t" . '<h2 class="upgrade">'.plog_tr('Updating Database').'</h2>';
				echo "\n\n\t\t" . '<ul class="info">';
				foreach ($return as $value) {
				echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
				}
				echo "\n\t\t" . '</ul>';
				echo "\n\n\t" . '<h2 class="upgrade">'.plog_tr('Done with database upgrade!').'</h2>';
				echo "\n\n\t" . '<form action="_upgrade.php?step=2" method="post">';
				echo "\n\t" . '<p><input class="submit" type="submit" name="next" value="'.plog_tr('Next Step').' &raquo;" /></p>';
				echo "\n\t" . '</form>' . "\n";
				break;
			}

		// Step 2 - move images, albums, collections, and uploads to new locations
		case 2:
			// Load the config file
			include_once(PLOGGER_DIR.'plog-load-config.php');
			// Check if we need to rename the directories due to permissions to force the re-creation of images/ and thumbs/
			if (isset($_SESSION['plogger_close_perms'])) {
				fix_open_perms($_SESSION['plogger_close_perms']);
			}
			$upgrade_images = upgrade_image_list();
			if ($upgrade_images['total'] > 0 || isset($_POST['upgrade-images'])) {
				$selects = array('5' => 5, '10' => 10, '25' => 25, '50' => 50, '75' => 75, '100' => 100, '150' => 150, '200' => 200, '250' => 250, '0' => plog_tr('All at once'));
				echo "\n\n\t" . '<h2 class="upgrade">'.plog_tr('Updating Images').'</h2>';
				if (!isset($_POST['upgrade-images'])) {
					echo "\n\n\t" . '<p class="actions">'.sprintf(plog_tr('Plogger needs to restructure %s items'), '<strong>'.$upgrade_images['total'].'</strong>') . '</p>';
					echo "\n\n\t" . '<form action="_upgrade.php?step=2" method="post">';
					echo "\n\n\t<p>".plog_tr('Number of images to update per cycle').': ';
					echo "\n\t\t" . '<select name="num-images">';
					foreach ($selects as $key => $value) {
						$selected = ($key == 0) ? ' selected="selected"' : '';
						echo "\n\t\t\t" . '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
					}
					echo "\n\t\t" . '</select>';
					echo "\n\t" . '</p>';
					echo "\n\n\t<p>* ".plog_tr('change this if you have a lot of images, or if you run into server timeout issues.');
					echo "\n\n\t" . '<p><input type="hidden" id="upgrade-images" name="upgrade-images" value="1" />';
					echo "\n\t" . '<input class="submit" type="submit" name="continue" value="'.plog_tr('Continue').'..." /></p>';
					echo "\n\n\t" . '</form>' . "\n";
				} else {
					$num_images = (isset($_POST['num-images']) && $_POST['num-images'] > 0) ? $_POST['num-images'] : $upgrade_images['total'];
					$return = upgrade_images($num_images, $upgrade_images);
					if (!empty($return['errors'])) {
						echo "\n\n\t" . '<p class="errors">'.plog_tr('Plogger was unable to move the following images. Please check your permissions.').'</p>';
						echo "\n\n\t\t" . '<ul class="info">';
						foreach ($return['errors'] as $value) {
							echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
						}
						echo "\n\t\t" . '</ul>';
					}
					if (!empty($return['output'])) {
						echo "\n\n\t" . '<p class="actions">'.plog_tr('Plogger was able to move the following images').':</p>';
						echo "\n\n\t\t" . '<ul class="info">';
						foreach ($return['output'] as $value) {
							echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
						}
						echo "\n\t\t" . '</ul>';
					}
					if ($return['count'] == $upgrade_images['total']) {
						echo "\n\n\t" . '<h2 class="upgrade">'.plog_tr('Done with image restructure').'!</h2>';
						echo "\n\n\t" . '<form action="_upgrade.php?step=3" method="post">';
						echo "\n\t" . '<p><input class="submit" type="submit" name="next" value="'.plog_tr('Next Step').' &raquo;" /></p>';
						echo "\n\t" . '</form>' . "\n";
						if (isset($_SESSION['plogger_close_perms'])) {
							unset($_SESSION['plogger_close_perms']);
						}
					} else {
						echo "\n\n\t" . '<p class="actions">'.sprintf(plog_tr('Plogger needs to restructure %s more images'), '<strong>'.( $upgrade_images['total'] - $return['count'] ).'</strong>').'</p>';
						echo "\n\n\t" . '<form action="_upgrade.php?step=2" method="post">';
						echo "\n\n\t<p>".plog_tr('Number of images to update per cycle').':';
						echo "\n\t\t" . '<select name="num-images">';
						foreach ($selects as $key => $value) {
							$selected = ($num_images == $key) ? ' selected="selected"' : '';
							echo "\n\t\t\t" . '<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
						}
						echo "\n\t\t" . '</select>';
						echo "\n\t" . '</p>';
						echo "\n\n\t" . '<p><input type="hidden" id="upgrade-images" name="upgrade-images" value="1" />';
						echo "\n\t" . '<input class="submit" type="submit" name="continue" value="'.plog_tr('Continue').'..." /></p>';
						echo "\n\n\t" . '</form>' . "\n";
					}
				}
				break;
			}

		// Step 3 - check for old themes & translation files
		case 3:
			$check_list = check_list();
			if (!empty($check_list['themes']) || !empty($check_list['translations'])) {
				if (!empty($check_list['themes'])) {
					echo "\n\n\t" . '<p class="actions">'.sprintf(plog_tr('Plogger has found old %s files'), plog_tr('theme') ).'. '.sprintf( plog_tr('If you have customized a theme listed below, please verify that you have a copy located in %s before moving on to the next step'), '<strong>plog-content/themes/</strong>' ).':</p>';
					echo "\n\n\t\t" . '<ul class="info">';
					foreach ($check_list['themes'] as $value) {
						echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
					}
					echo "\n\t\t" . '</ul>';
				}
				if (!empty($check_list['translations'])) {
					echo "\n\n\t" . '<p class="actions">'.sprintf(plog_tr('Plogger has found old %s files'), plog_tr('translation') ).'. '.sprintf(plog_tr('Please verify that you have a copy located in %s before moving on to the next step'), '<strong>plog-content/translations/</strong>' ).':</p>';
					echo "\n\n\t\t" . '<ul class="info">';
					foreach ($check_list['translations'] as $value) {
						echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
					}
					echo "\n\t\t" . '</ul>';
				}
				echo "\n\n\t" . '<form action="_upgrade.php?step=4" method="post">';
				echo "\n\t" . '<p><input class="submit" type="submit" name="next" value="'.plog_tr('Next Step').' &raquo;" /></p>';
				echo "\n\t" . '</form>' . "\n";
				break;
			}

		// Step 4 - clean up the old files
		case 4:
			// Load the config file
			include_once(PLOGGER_DIR.'plog-load-config.php');
			$cleanup_list = cleanup_list();
			if (!empty($cleanup_list['files']) || !empty($cleanup_list['folders']) || isset($_POST['do-cleanup'])) {
				echo "\n\n\t" . '<h2 class="upgrade">'.plog_tr('Cleaning Up Files').'</h2>';
				if (!isset($_POST['do-cleanup'])) {
					echo "\n\n\t" . '<form action="_upgrade.php?step=4" method="post">';
					echo "\n\n\t" . '<p class="actions">'.plog_tr('Plogger has found the following files/folders that are no longer needed').':</p>';
					echo "\n\n\t\t" . '<ul class="info">';
					foreach ($cleanup_list['files'] as $value) {
						echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
					}
					foreach ($cleanup_list['folders'] as $value) {
						echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
					}
					echo "\n\t\t" . '</ul>';
					echo "\n\n\t" . '<p>'.sprintf(plog_tr('You can have Plogger attempt to %s for you, or you can delete them manually via FTP and go to the next step.'), '<input class="submit-inline" type="submit" name="delete" value="'.plog_tr('delete the files').'..." />').'';
					echo "\n\t" . '<input type="hidden" id="do-cleanup" name="do-cleanup" value="1" /></p>';
					echo "\n\n\t" . '</form>';
				} else {
					$return = cleanup_files($cleanup_list['files'], $cleanup_list['folders']);
					if (!empty($return['errors'])) {
						echo "\n\n\t" . '<p class="errors">'.plog_tr('Plogger could not delete the following files/folders. Please check your permissions or delete them manually.').'</p>';
						echo "\n\n\t\t" . '<ul class="info">';
						foreach ($return['errors'] as $value) {
							echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
						}
						echo "\n\t\t" . '</ul>';
					}
					if (!empty($return['output'])) {
						echo "\n\n\t" . '<p class="actions">'.plog_tr('Plogger was able to delete the following files/folders').':</p>';
						echo "\n\n\t\t" . '<ul class="info">';
						foreach ($return['output'] as $value) {
							echo "\n\t\t\t" . '<li class="margin-5">'.$value.'</li>';
						}
						echo "\n\t\t" . '</ul>';
					}
					if (!empty($return['errors'])) {
						echo "\n\t" . '<form action="_upgrade.php?step=4" method="post">';
						echo "\n\t" . '<p style="float: left;"><input type="hidden" id="do-cleanup" name="do-cleanup" value="1" />';
						echo "\n\t" . '<input class="submit" type="submit" name="try again" value="'.plog_tr('Try Again').'" /></p>';
						echo "\n\t" . '</form>'. "\n";
					} else {
						echo "\n\n\t" . '<h2 class="upgrade">'.plog_tr('Done with cleanup!').'</h2>';
					}
				}
				echo "\n\n\t" . '<form action="_upgrade.php?step=5" method="post">';
				echo "\n\t" . '<p style="float: left;"><input class="submit" type="submit" name="next" value="'.plog_tr('Next Step').' &raquo;" /></p>';
				echo "\n\t" . '</form>'. "\n";
				echo "\n\t" . '<p>&nbsp;</p>'. "\n";
				break;
			}

		// Finished!
		case 5:
			echo "\n\n\t" . '<h2 class="upgrade">'.plog_tr('Upgrade complete!').'</h2>';
			echo "\n\n\t" . '<p class="info">'.plog_tr('You have successfully upgraded Plogger!').'</p>';
			if (is_open_perms(PLOGGER_DIR.'plog-content/')) {
				echo "\n\n\t" . '<p class="actions">'.sprintf(plog_tr('You can now CHMOD the %s directory back to 0755'), '<strong>plog-content/</strong>').'.</p>';
			}
			echo "\n\n\t" . '<form action="index.php" method="post">';
			echo "\n\t" . '<p><input class="submit" type="submit" name="next" value="'.plog_tr('Log In').'" /></p>';
			echo "\n\t" . '</form>'. "\n";
			break;
	}
}

if (!$beta1) {
	close_db();
	close_ftp();
}

?>

</body>
</html>