INSTALLATION
===========
To install, upload all of the files in the Plogger distribution to your web server.
Next, create a MySQL database and user from your web hosting control panel.
Then, run the install script (/plog-admin/_install.php) in the web browser of your choice.
The script will guide you through the rest of the installation process.

INTEGRATION
==========

To integrate Plogger into your website, place the following PHP statements in the .php
file you wish display the gallery in:

First line of the .php file ->										<?php require("path/to/plogger.php"); ?>
In the HEAD section ->												<?php the_plogger_head(); ?>
In the BODY section where you want the gallery ->		<?php the_plogger_gallery(); ?>

Version: 1.0-RC1

UPGRADE FROM 1.0-Beta1, 1.0-Beta2, 1.0-Beta3
==================================

1) Make a backup of all of your files via FTP and your database via phpMyAdmin and save
	them to your computer.
2) Upload and overwrite ALL FILES except for plog-config.php.
3) Run /plog-admin/_upgrade.php in your web browser.
4) The upgrade process consists of 5 steps and is automated. Follow the instructions for each
	step and fix any problems listed before proceeding to the next step.
