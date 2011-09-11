<?php
header('Content-Type: text/html; charset=utf-8');
global $inHead;

// Load configuration variables from database, plog-globals, & plog-includes/plog-functions
require_once(dirname(dirname(__FILE__)).'/plog-load-config.php');

// Login/logout/reset password functions
if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'log_in':
			// Check the username and password
			if ((isset($_POST['plog_username']) && $_POST['plog_username'] == $config['admin_username']) && (isset($_POST['plog_password']) && md5($_POST['plog_password']) == $config['admin_password'])) {
				session_regenerate_id();
				$_SESSION['plogger_logged_in'] = true;
				// Clear out the activation key on login if set
				if (isset($config['activation_key']) && !empty($config['activation_key'])) {
					$query = "UPDATE `".PLOGGER_TABLE_PREFIX."config` SET `activation_key` = ''";
					$result = run_query($query);
				}
			} else {
				// Handle error for wrong username / password
				$redirect = basename($_SERVER['PHP_SELF']);
				header('Location: index.php?loginerror&r='.$redirect);
				exit;
			}
			break;
		case 'log_out':
			// Handle logging out of the session
			$_SESSION = array();
			session_destroy();
			header('Location: index.php?loggedout');
			exit;
		case 'password_reset':
			// Handle logout in case someone is already logged in during the password reset?
			$_SESSION = array();
			session_destroy();
			// Handle default password reset error: invalid usename or email address
			$reset_output = 'resetpassword&reseterror=1';
			if (isset($_POST['admin_email']) && ($_POST['admin_email'] == $config['admin_email'] || $_POST['admin_email'] == $config['admin_username'])) {
				// Change output to handle email sent success message
				$reset_output = 'checkemail=1';
				$from = str_replace('www.', '', $_SERVER['HTTP_HOST']);
				$key = md5(generate_password().time().$config['admin_password']);
				ini_set('sendmail_from', 'noreply@'.$from); // set for windows machines
				if (!@mail( $config['admin_email'],
										'[Plogger] '.plog_tr('Reset Password'),
										plog_tr('Someone has requested Plogger to reset the password for the following website and username.'). "\n\n".
										plog_tr('Website').': '.$config['gallery_url']. "\n".
										plog_tr('Username').': '.$config['admin_username']. "\n\n".
										plog_tr('Follow the link below to reset your password; otherwise, just ignore this email and nothing will happen.'). "\n\n".
										$config['gallery_url'].'plog-admin/plog-admin.php?action=password_reset&key='.$key,
										'From: Plogger <noreply@'.$from.'>'
									)) {
					// Change output to handle error with mail() function
					$reset_output = 'reseterror=3';
				} else {
					// Only update the activation key if an email is sent
					$query = "UPDATE `".PLOGGER_TABLE_PREFIX."config` SET `activation_key` = '${key}'";
					$result = run_query($query);
				}
			}
			if (isset($_GET['key'])) {
				if (!empty($_GET['key']) && $_GET['key'] == $config['activation_key']) {
					// Handle verification success message
					$reset_output = 'checkemail=2';
					$password = generate_password();
					$from = str_replace('www.', '', $_SERVER['HTTP_HOST']);
					ini_set('sendmail_from', 'noreply@'.$from); // Set for Windows machines
					if (!@mail( $config['admin_email'],
										'[Plogger] '.plog_tr('New Password'),
										plog_tr('Plogger has reset your password for the following website and username.'). "\n\n".
										plog_tr('Website').': '.$config['gallery_url']. "\n".
										plog_tr('Username').': '.$config['admin_username']. "\n\n".
										plog_tr('Your new password is').': '.$password. "\n\n".
										plog_tr('Log in').': '.$config['gallery_url'].'plog-admin/?checkemail=3',
										'From: Plogger <noreply@'.$from.'>'
									)) {
						// Change output to handle error with mail() function
						$reset_output = 'reseterror=3';
					} else {
						// Only update the password if an email is sent
						$query = "UPDATE `".PLOGGER_TABLE_PREFIX."config` SET `admin_password` = MD5('${password}')";
						$result = run_query($query);
					}
				} else {
					// Handle bad verification key error
					$reset_output = 'reseterror=2';
				}
			}
			header('Location: index.php?'.$reset_output);
			exit;
	}
}

// Load the admin functions only after the login has been determined
require_once(PLOGGER_DIR.'plog-admin/plog-admin-functions.php');

if (!isset($_SESSION['plogger_logged_in']) || $_SESSION['plogger_logged_in'] !== true) {
	$redirect = basename($_SERVER['PHP_SELF']);
	header('Location: index.php?r='.$redirect);
	exit;
}

// Display admin tabs
function display($string, $current) {
	global $inHead;
	global $config;

	$tabs = array();
	$tabs['upload']	= array('url' => 'plog-upload.php', 'caption' => plog_tr('<em>U</em>pload'));
	$tabs['import']		= array('url' => 'plog-import.php?nojs=1', 'caption' => plog_tr('<em>I</em>mport'), 'onclick' => "window.location='plog-import.php'; return false;");
	$tabs['manage']	= array('url' => 'plog-manage.php', 'caption' => plog_tr('<em>M</em>anage'));
	$tabs['feedback']	= array('url' => 'plog-feedback.php', 'caption' => plog_tr('<em>F</em>eedback'));
	$tabs['options']	= array('url' => 'plog-options.php', 'caption' => plog_tr('<em>O</em>ptions'));
	$tabs['themes']	= array('url' => 'plog-themes.php', 'caption' => plog_tr('<em>T</em>hemes'));
	$tabs['plugins']	= array('url' => 'plog-plugins.php', 'caption' => plog_tr('<em>P</em>lugins'));
	$tabs['view']		= array('url' => $config['gallery_url'], 'caption' => plog_tr('<em>V</em>iew'), 'onclick' => "window.open('".$config['gallery_url']."'); return false;");
	$tabs['support']	= array('url' => 'http://www.plogger.org/forum/', 'caption' => plog_tr('<em>S</em>upport'), 'onclick' => "window.open('http://www.plogger.org/forum/'); return false;");
	$tabs['logout']		= array('url' => $_SERVER['PHP_SELF'].'?action=log_out', 'caption' => plog_tr('<em>L</em>og out'));
	// Get the accesskey from the localization - it should be surrounded by <em> tags
	foreach($tabs as $key => $data) {
		if (preg_match('|<em>(.*)</em>|', $data['caption'], $matches)) {
			$tabs[$key]['accesskey'] = $matches[1];
		}
	}

$output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Plogger '.plog_tr('Gallery Admin').'</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="'.$config['gallery_url'].'plog-admin/css/admin.css" type="text/css" media="all" />
	<link rel="stylesheet" href="'.$config['gallery_url'].'plog-admin/css/lightbox.css" type="text/css" media="all" />
	<script type="text/javascript" src="'.$config['gallery_url'].'plog-admin/js/prototype.js"></script>
	<script type="text/javascript" src="'.$config['gallery_url'].'plog-admin/js/plogger.js"></script>
	<script type="text/javascript" src="'.$config['gallery_url'].'plog-admin/js/lightbox.js"></script>
	'.$inHead.'
</head>

<body onload="initLightbox();">

<div id="header">

	<div id="logo">
		<img src="'.$config['gallery_url'].'plog-admin/images/plogger.gif" width="393" height="90" alt="Plogger" />
	</div><!-- /logo -->

	<div id="plogger-version">
		<div class="align-right">
			'.$config['version'].'&nbsp;&nbsp;&nbsp;['.plogger_show_server_info_link().']
		</div><!-- /align-right -->
		'.plogger_generate_server_info().'
	</div><!-- /plogger-version -->

	<div style="clear: both; height: 15px;">&nbsp;</div>

	<div id="tab-nav">
		<ul>';
		foreach($tabs as $tab => $data) {
		$output .= '
			<li';
			if ($current == $tab) $output .= ' id="current"';
			$output .= '><a';
			if (!empty($data['onclick'])) $output .= ' onclick="'.$data['onclick'].'"';
			if (!empty($data['accesskey'])) $output .= ' accesskey="'.$data['accesskey'].'"';
			$output .= ' href="'.$data['url'].'">'.$data['caption'].'</a></li>';
		}
		$output .= '
		</ul>
	</div><!-- /tab-nav -->

</div><!-- /header -->

<div id="content">
'.$string.'
</div><!-- /content -->';

if (defined('PLOGGER_DEBUG') && PLOGGER_DEBUG == '1') {
	$output .= trace('Queries: '.$GLOBALS['query_count'], false);
	foreach ($GLOBALS['queries'] as $q) {
		$output .= trace($q, false);
	}
	$output .= trace(plog_timer('end'), false);
}

$output .= "\n\n" . '</body>
</html>';

echo $output;

close_db();
close_ftp();
exit;
}

?>