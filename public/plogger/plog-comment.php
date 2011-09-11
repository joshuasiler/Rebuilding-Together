<?php
/* Plogger comment script: writes comment information to the database and links it to the picture using the pictures ID */

include_once(dirname(__FILE__).'/plog-load-config.php');

// Remove plog-comment from the end, if present .. is there a better way to determine the full url?
// Workaround for never-ending comment loop
$is_comment = strpos($config['baseurl'], 'plog-comment.php');
if ($is_comment !== false) {
	$config['baseurl'] = substr($config['baseurl'], 0, $is_comment);
}

// Loosely validate url string format without actually checking the link (cause that takes time)
function is_valid_url($url) {
	if (preg_match('#^http\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $url)) {
		return 'http';
	} else if (preg_match('#^[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $url)) {
		return 'nohttp';
	} else {
		return 'badurl';
	}
}

function is_valid_email($email) {
	// Based on the is_email function from WordPress with some additional checks
	// Check that there is an @, a dot, no double dots, does not start with a dot, or have a dot next to the @ symbol
	if (strpos($email, '@') !== false && strpos($email, '.') !== false && strpos($email, '..') === false && $email[0] != '.' && $email[strrpos($email, '@')-1] != '.') {
		// check for the correct syntax
		if (preg_match("/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,}\$/i", $email)) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// Set up our error arrays
$errors = array();
$error_field = array();

// Set up all the necessary variables
$parent_id = intval($_POST['parent']);
$author = $email = $url = $comment = '';

$pic = get_picture_by_id($parent_id);

// Check for a redirect, referrer, or default back to the generic Plogger URL
if (isset($_POST['redirect'])) {
	$redirect = $_POST['redirect'];
} else if (isset($_SERVER['HTTP_REFERRER']) && !empty($_SERVER['HTTP_REFERRER'])) {
	$redirect = $_SERVER['HTTP_REFERRER'];
} else {
	$redirect = generate_url('picture', $parent_id);
}

if ($config['allow_comments'] && $pic['allow_comments']) {
	if (isset($_POST['plogger-token']) && isset($_SESSION['plogger-token']) && $_POST['plogger-token'] === $_SESSION['plogger-token']) {
		// Verify the author / name
		if (isset($_POST['author']) && $_POST['author'] != '') {
			$author = strip_tags(SmartStripSlashes($_POST['author']));
		} else {
			$author = '';
			$errors[] = plog_tr('Author name is missing.');
			$error_field[] = 'author';
		}
		// Verify the email
		if (isset($_POST['email']) && $_POST['email'] != '') {
			if (is_valid_email(strip_tags(SmartStripSlashes($_POST['email'])))) {
				$email = SmartStripSlashes($_POST['email']);
			} else {
				$email = '';
				$errors[] = plog_tr('The email address you entered does not appear to be valid.');
				$error_field[] = 'email';
			}
		} else {
			$email = '';
			$errors[] = plog_tr('You forgot to enter an email.');
			$error_field[] = 'email';
		}
		// Verify the website url if set
		if (isset($_POST['url']) && $_POST['url'] != '') {
			if (is_valid_url($_POST['url']) == 'http') {
				$url = $_POST['url'];
			} else if (is_valid_url($_POST['url']) == 'nohttp') {
				$url = 'http://'.$_POST['url'];
			} else {
				$url = '';
				$errors[] = plog_tr('The website URL you entered does not appear to be valid.');
				$error_field[] = 'url';
			}
		} else {
			$url = '';
		}
		// Verify the comment
		if (isset($_POST['comment']) && $_POST['comment'] != '') {
			// should we strip tags out for now and put limited allowability in later?
			$comment = strip_tags(SmartStripSlashes($_POST['comment']));
		} else {
			$comment = '';
			$errors[] = plog_tr('You forgot to enter a comment.');
			$error_field[] = 'comment';
		}

		// If the captcha is required, check it here
		if (isset($_SESSION['require_captcha']) && $_SESSION['require_captcha'] === true) {
			if (!isset($_POST['captcha']) || !isset($_SESSION['captcha']) || $_POST['captcha'] != $_SESSION['captcha']) {
				$errors[] = plog_tr('CAPTCHA check failed.');
				$error_field[] = 'captcha';
			}
		}

		if (empty($errors)) {
			$rv = add_comment($parent_id, $author, $email, $url, $comment);
			// We're done with this so empty it out to stop double posts
			unset($_POST);
			if (isset($rv['errors'])) {
				$errors = $rv['errors'];
			} else if ($config['comments_moderate']) {
				$_SESSION['comment_moderated'] = 1;
			}
		}
		unset($_SESSION['plogger-token']);
	} else {
		// Missing form token
		$errors = array(plog_tr('Spam token missing or does not match!'));
	}
} else {
	// Comments are not on
	$errors = array(plog_tr('Comments are disabled. You are unable to add a comment!'));
}

if (!empty($errors)) {
	// Set the errors for form display
	$_SESSION['comment_post_error'] = $errors;
	// Set the session form variables so users don't have to re-enter their information
	$_SESSION['plogger-form'] = array(
		'author' => $author,
		'email' => $email,
		'url' => $url,
		'comment' => $comment
	);
	$_SESSION['plogger-form-error'] = $error_field;
} else {
	// Clear out the session form variables if no errors
	unset($_SESSION['plogger-form']);
	unset($_SESSION['plogger-form-error']);
}

close_db();

// Redirect back
header('Location: '.$redirect);

?>