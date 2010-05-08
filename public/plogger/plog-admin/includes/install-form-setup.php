<?php
if (basename($_SERVER['PHP_SELF']) == basename( __FILE__ )) {
	// ignorance is bliss
	exit();
}

if (!defined('PLOGGER_DIR')) {
	return false;
}

// Most of the time it's probably running on localhost
if (empty($form['db_host'])) {
	$form['db_host'] = 'localhost';
}
if (empty($form['ftp_host'])) {
	$form['ftp_host'] = 'localhost';
}

$init_vars = array('db_user', 'db_pass', 'db_name', 'gallery_name', 'admin_username', 'admin_email', 'ftp_user', 'ftp_pass', 'ftp_path');
foreach($init_vars as $var) {
	if (empty($form[$var])) {
		$form[$var] = '';
	}
}
?>
<h1><?php echo plog_tr('Plogger Configuration Setup') ?></h1>

	<p><?php echo plog_tr('To install, simply fill out the following form. If there are any problems, you will be notified and asked to fix them before the installation will continue. After the installation is complete, you will be redirected to the Plogger Gallery Admin page.') ?></p>

	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<p><input type="hidden" name="action" value="install" /></p>
	<table>
<?php if (!defined('PLOGGER_DB_HOST')) { ?>
		<tr>
			<td colspan="2"><h2><?php echo plog_tr('Database Setup') ?></h2></td>
		</tr>
		<tr>
			<td class="form_label"><label for="db_host"><?php echo plog_tr('MySQL Host') ?>:</label></td>
			<td class="form_input"><input type="text" name="db_host" id="db_host" value="<?php echo $form['db_host']; ?>" /></td>
		</tr>
		<tr>
			<td class="form_label"><label for="db_user"><?php echo plog_tr('MySQL Username') ?>:</label></td>
			<td class="form_input"><input type="text" name="db_user" id="db_user" value="<?php echo $form['db_user']; ?>" /></td>
		</tr>
		<tr>
			<td class="form_label"><label for="db_pass"><?php echo plog_tr('MySQL Password') ?>:</label></td>
			<td class="form_input"><input type="password" name="db_pass" id="db_pass" value="<?php echo $form['db_pass']; ?>" /></td>
		</tr>
		<tr>
			<td class="form_label"><label for="db_name"><?php echo plog_tr('MySQL Database') ?>:</label></td>
			<td class="form_input"><input type="text" name="db_name" id="db_name" value="<?php echo $form['db_name']; ?>" /></td>
		</tr>
<?php
} // End database setup

if (
	!isset($config['gallery_name']) ||
	!isset($config['admin_email']) ||
	!isset($config['admin_username']) ||
	!isset($config['admin_password'])
) {
?>
		<tr>
			<td colspan="2"><h2><?php echo plog_tr('Administrative Setup') ?></h2></td>
		</tr>
	<?php if (!isset($config['gallery_name'])) { ?>
		<tr>
			<td class="form_label"><label for="gallery_name"><?php echo plog_tr('Gallery Name') ?>:</label></td>
			<td class="form_input"><input type="text" name="gallery_name" id="gallery_name" value="<?php echo $form['gallery_name']; ?>" /></td>
		</tr>
<?php
	}
	if (!isset($config['admin_email'])) {
?>
		<tr>
			<td class="form_label"><label for="admin_email"><?php echo plog_tr('Your Email') ?>:</label></td>
			<td class="form_input"><input type="text" name="admin_email" id="admin_email" value="<?php echo $form['admin_email']; ?>" /></td>
		</tr>
<?php
	}
	if (!isset($config['admin_username'])) {
?>
		<tr>
			<td class="form_label"><label for="admin_username"><?php echo plog_tr('Username') ?>:</label></td>
			<td class="form_input"><input type="text" name="admin_username" id="admin_username" value="<?php echo $form['admin_username']; ?>" /></td>
		</tr>
<?php
	}
	if (!isset($config['admin_password'])) {
?>
		<tr>
			<td class="form_label"><label for="admin_password"><?php echo plog_tr('Password') ?>:</label></td>
			<td class="form_input"><input type="password" name="admin_password" id="admin_password" value="" /></td>
		</tr>
		<tr>
			<td class="form_label"><label for="admin_password_confirm"><?php echo plog_tr('Confirm Password') ?>:</label></td>
			<td class="form_input"><input type="password" name="admin_password_confirm" id="admin_password_confirm" value="" /></td>
		</tr>
<?php
	}
} // End administrative setup

// If server is safe_mode enabled, prompt user for FTP info for FTP workaround
if (ini_get('safe_mode')) {
	if (function_exists('ftp_connect')) {
?>
		<tr>
			<td colspan="2">
				<h2><?php echo plog_tr('Safe_mode FTP workaround') ?></h2>
				<p><?php echo plog_tr('Safe mode has been detected on your server. FTP access is needed to allow Plogger to work correctly with safe_mode enabled.') ?></p>
			</td>
		</tr>
		<tr>
			<td class="form_label"><label for="ftp_host"><?php echo plog_tr('FTP Host') ?>:</label></td>
			<td class="form_input"><input type="text" name="ftp_host" id="ftp_host" value="<?php echo $form['ftp_host']; ?>" /></td>
		</tr>
		<tr>
			<td class="form_label"><label for="ftp_user"><?php echo plog_tr('FTP Username') ?>:</label></td>
			<td class="form_input"><input type="text" name="ftp_user" id="ftp_user" value="<?php echo $form['ftp_user']; ?>" /></td>
		</tr>
		<tr>
			<td class="form_label"><label for="ftp_password"><?php echo plog_tr('FTP Password') ?>:</label></td>
			<td class="form_input"><input type="password" name="ftp_pass" id="ftp_pass" value="<?php echo $form['ftp_pass']; ?>" /></td>
		</tr>
		<tr>
			<td class="form_label"><label for="ftp_path"><?php echo plog_tr('FTP Path to Plogger Base Folder (from FTP login)') ?>:</label></td>
			<td class="form_input"><input type="text" name="ftp_path" id="ftp_path" value="<?php echo $form['ftp_path']; ?>" /></td>
		</tr>
<?php
	} else {
	// Cannot use workaround, but safe mode is still enabled
?>
		<tr>
			<td colspan="2">
				<h2><?php echo plog_tr('Safe_mode Detected') ?></h2>
				<p><?php echo plog_tr("Safe_mode has been detected on your server and your server is missing PHP's FTP commands. Plogger cannot function correctly with safe_mode enabled and without FTP access.") ?></p>
			</td>
		</tr>
<?php
	}
} // End safe_mode FTP workaround
?>
		<tr>
			<td class="submitButtonRow" colspan="2"><input type="submit" class="submit" name="submit" id="submit" value="<?php echo plog_tr('Proceed') ?>" /></td>
		</tr>
	</table>
	</form>
