<?php
/* You can manually modify this file before installing (renaming this file to plog-config.php before
 * installation) or you can let Plogger generate the file automatically by running the installation script
 * (run plog-admin/_install.php in your browser).

 * If you want to change the database connection information, you may also edit this file manually
 * after Plogger has been installed. */

/* MySQL hostname */
define('PLOGGER_DB_HOST', '');

/* MySQL database username */
define('PLOGGER_DB_USER', '');

/* MySQL database password */
define('PLOGGER_DB_PW', '');

/* The name of the database for Plogger */
define('PLOGGER_DB_NAME', '');

/* Define the Plogger database table prefix. You can have multiple installations in one database if you give
 * each a unique prefix. Only numbers, letters, and underscores are permitted (i.e., plogger_). */
define('PLOGGER_TABLE_PREFIX', 'plogger_');

/* Define the Plogger directory permissions. Change permissions if you are having issues with images or
 * sub-directories being saved, moved, or deleted from the Plogger-created directories (i.e. Collections
 * or Albums) */
define('PLOGGER_CHMOD_DIR', 0755);

/* Define the Plogger file permissions. Change permissions if you are having issues with viewing,
 * deleting, or moving images within Plogger (i.e. Pictures) */
define('PLOGGER_CHMOD_FILE', 0644);

/* Is Plogger embedded in another program, like WordPress?
 * 1/0 (True/False) if set will overrule automatic check */
define('PLOGGER_EMBEDDED', '');

/* Define a directory path to save session variables if you are having trouble logging in or Plogger is
 * telling you that you have session.save_path issues and/or if your server php.ini setup has a
 * blank session.save_path php.ini variable */
define('PLOGGER_SESSION_SAVE_PATH', '');

/* Plogger localized language, defaults to English. Change this to localize Plogger.
 * A corresponding MO file for the chosen language must be installed in /plog-content/translations/.
 * For example, upload de.mo to /plog-content/translations/ and set PLOGGER_LOCALE to 'de' to
 * enable German language support.
 * Example language codes: da, de, et, fr, pl, ro, tr, en-CA (for Canadian English) */
define('PLOGGER_LOCALE', '');

/* Turn on debug mode if trying to troubleshoot issues.
 * 1/0 (True/False) if set will display debug messages at bottom of gallery and admin pages
 * Do not leave this running if gallery is functioning properly. */
define('PLOGGER_DEBUG', '');

?>