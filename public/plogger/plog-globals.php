<?php
@ini_set('include_path', ini_get('include_path'));
@ini_set('arg_separator.output', '&amp;');

if (intval(ini_get('max_execution_time')) < 300) {
	@ini_set('max_execution_time', '300');
}

if (intval(ini_get('memory_limit')) < 64) {
	@ini_set('memory_limit', '64M');
}

// clean up $_SERVER['PHP_SELF'] so it's safe to use against potential XSS attacks
$phpself = basename(realpath($_SERVER['SCRIPT_FILENAME']));
$_SERVER['PHP_SELF'] = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], $phpself)) . $phpself;

error_reporting(E_ALL);

define('PLOGGER_DIR', dirname(__FILE__).'/');

// If we're using an old plog-config file, define the new constants
if (!defined('PLOGGER_CHMOD_DIR')) {
	define('PLOGGER_CHMOD_DIR', 0755);
}
if (!defined('PLOGGER_CHMOD_FILE')) {
	define('PLOGGER_CHMOD_FILE', 0644);
}

if (!defined('PLOGGER_TABLE_PREFIX')) {
	define('PLOGGER_TABLE_PREFIX', 'plogger_');
}

$config_table = PLOGGER_TABLE_PREFIX.'config';

define('THUMB_SMALL', 1);
define('THUMB_LARGE', 2);
define('THUMB_RSS', 3);
define('THUMB_NAV', 4);
define('THUMB_THEME', 5);

// Start the session
if (!headers_sent() && !session_id()) {
	// Set the session.save_path if user defined in plog-config
	if (defined('PLOGGER_SESSION_SAVE_PATH') && PLOGGER_SESSION_SAVE_PATH != '') {
		session_save_path(PLOGGER_SESSION_SAVE_PATH);
	}
	session_start();
}

if (!class_exists('streamreader')) {
	require_once(PLOGGER_DIR.'plog-includes/lib/gettext/streams.php');
	require_once(PLOGGER_DIR.'plog-includes/lib/gettext/gettext.php');
}

if (defined('PLOGGER_LOCALE') && PLOGGER_LOCALE !== '' && strlen(PLOGGER_LOCALE) >= 2) {
	$locale = PLOGGER_LOCALE;
} else {
	$locale = 'en_US';
}

$language = strtolower(substr($locale, 0, 2));

$mofile = PLOGGER_DIR.'plog-content/translations/'.$language.'.mo';

// If the mo file does not exist or is not readable, or if the locale is en_US, do not load the mo
if (is_readable($mofile) && ($locale != 'en_US')) {
	$input = new FileReader($mofile);
} else {
	$input = false;
}

$plog_l10n = new gettext_reader($input);

// Return a translated string
function plog_tr($text) {
	global $plog_l10n;

	if (isset($plog_l10n)) {
		return $plog_l10n->translate($text);
	} else {
		return $text;
	}
}

?>