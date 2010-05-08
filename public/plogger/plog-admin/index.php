<?php
// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');

// If we're already logged in, redirect to the upload page
if (isset($_SESSION['plogger_logged_in']) && $_SESSION['plogger_logged_in'] === true) {
	header('Location: plog-upload.php');
	exit;
}

// Handle redirects for the form
if (isset($_GET['r'])) {
	$form_action = $_GET['r'];
} else {
	$form_action = 'plog-upload.php';
}

$output = '';

/* Action messages */
// Logout message
if (isset($_REQUEST['loggedout'])) {
	$output .= "\t" . '<div id="login-action">'.plog_tr('You have been successfully logged out').'</div>' . "\n\n";
}

// Reset password messages
if (isset($_REQUEST['checkemail'])) {
	switch($_REQUEST['checkemail']) {

		case 1: // Reset password request completed, check email for confirmation link
		$output .= "\t" . '<div id="login-action">'.plog_tr('Please check your email for the confirmation link').'.</div>' . "\n\n";
		break;

		case 2: // Verification successful, password has been reset, check email
		$output .= "\t" . '<div id="login-action">'.plog_tr('Verification successful. Please check your email for the new password').'.</div>' . "\n\n";
		break;

		case 3: // Password has been reset, login and reset password to permanent one
		$output .= "\t" . '<div id="login-action">'.plog_tr('Please change your password after logging in').'.</div>' . "\n\n";
		break;

	}
}

/* Error messages */
// Login error message
if (isset($_REQUEST['loginerror'])) {
	// Invalid login info, either username or password didn't match
	$output .= "\t" . '<div id="login-error">'.plog_tr('Invalid username or password').'</div>' . "\n\n";
}

// Reset password errors
if (isset($_REQUEST['reseterror'])) {
	switch($_REQUEST['reseterror']) {

		case 1: // Password reset - invalid username or email address
		$output .= "\t" . '<div id="login-error">'.plog_tr('Invalid admin username or email address').'</div>' . "\n\n";
		break;

		case 2: // Password reset - Verification link is invalid or expired
		$output .= "\t" . '<div id="login-error">'.plog_tr('Sorry, that verification key does not appear to be valid').'.</div>' . "\n\n";
		break;

		case 3: // Password reset - email could not be sent
		$output .= "\t" . '<div id="login-error">'.plog_tr('The email could not be sent. Possible reason: your host may have disabled the mail() function.').'</div>' . "\n\n";
		break;
	}
}

// Create the form information
if(isset($_REQUEST['resetpassword'])) {
	$output .= "\t" . '<div id="login-action">'.plog_tr('Please enter your username or email address and you will be notified via email the password reset process.').'</div>' . "\n\n";
	$form_content = '<p><label for="admin_email">'.plog_tr('Username or Email').'</label></p>
		<p><input type="text" name="admin_email" id="admin_email" tabindex="10" /></p>
		<p><input type="hidden" name="action" value="password_reset" /><input class="submit" type="submit" value="'.plog_tr('Reset Password').'" tabindex="30" /></p>' . "\n";
	$link = '<a title="'.plog_tr('Log in').'" href="'.$config['gallery_url'].'plog-admin/index.php">'.plog_tr('Log in').'</a>' . "\n";
} else {
	$form_content = '<p><label for="plog_username">'.plog_tr('Username').':</label> &nbsp;<input type="text" name="plog_username" id="plog_username" tabindex="10" /></p>
		<p><label for="plog_password">&nbsp;'.plog_tr('Password').':</label> &nbsp;<input type="password" name="plog_password" id="plog_password" tabindex="20" /></p>
		<p><input type="hidden" name="action" value="log_in" /><input class="submit" type="submit" value="'.plog_tr('Log In').'" tabindex="50" /></p>' . "\n";
	$link = '<a title="'.plog_tr('Reset password').'" href="'.$config['gallery_url'].'plog-admin/index.php?resetpassword">'.plog_tr('Reset password').'</a>' . "\n";
}

close_db();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Plogger <?php echo plog_tr('Gallery Admin | Login') ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="<?php echo $config['gallery_url'] ?>plog-admin/css/login.css" type="text/css" media="all" />
	<script type="text/javascript" src="<?php echo $config['gallery_url'] ?>plog-admin/js/plogger.js"></script>
</head>

<body id="login-page" onload="focus_first_input()">

	<div id="login-logo">
		<img src="<?php echo $config['gallery_url'] ?>plog-admin/images/login-logo.gif" width="334" height="89" alt="Plogger" />
	</div><!-- /login-logo -->

<?php echo $output ?>
	<div id="login-box">
		<form action="<?php echo $form_action ?>" method="post">
		<?php echo $form_content ?>
		</form>
	</div><!-- /login-box -->

	<div id="login-nav">
		<a title="<?php echo plog_tr('View gallery') ?>" href="<?php echo $config['gallery_url'] ?>"><?php echo plog_tr('View gallery') ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<?php echo $link ?>
	</div><!-- /login-nav -->

</body>
</html>