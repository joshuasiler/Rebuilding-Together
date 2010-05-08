<?php
/*
Plugin Name: Latest Images
Description: This script allows you to include a specified number of thumbnails of the latest images from your Plogger gallery on your website.
Version: 1.0
Author: Kim Parsell and Mike Conover
Author URI: http://plogger.org/
Credits: The original script was contributed by theg3nius (http://www.plogger.org/forum/discussion/77/plogger-random-image-display-script/) and has been modified.
License: GNU General Public License

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

/* ignorance is bliss */
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	exit();
}

/* Pull in Plogger files to define filepaths and functions */
include_once(dirname(dirname(dirname(dirname(__FILE__)))).'/plogger.php');

if (!defined('PLOGGER_DIR')) {
	return false;
}

global $config;

/* Defines the URL of your Plogger gallery */
// $plog_latest_images_site_url = 'http://www.yoursite.com/plogger/embedded.php';
$plog_latest_images_site_url = $config['gallery_url'];

/* How many images do you want to show? */
$plog_latest_images_amount = '5';

/* The database query to pull the latest images from the database. Comment out if you are pulling images from a specific collection below. */
$plog_latest_images_query = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."pictures ORDER BY `id` DESC LIMIT $plog_latest_images_amount";

/* The database query to pull the latest images from a specific collection. Uncomment and change the X to the ID of the desired collection. */
//$plog_latest_images_query = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."pictures WHERE `parent_collection` = X ORDER BY `id` DESC LIMIT $plog_latest_images_amount";

$plog_latest_images_result = mysql_query($plog_latest_images_query) or die ("Could not execute query: $plog_latest_images_query." .mysql_error());

$config['baseurl'] = $plog_latest_images_site_url;

/* The loop */
while ($row = mysql_fetch_array($plog_latest_images_result)) {
	$id = $row['id'];
	$path = $row['path'];
	$caption = SmartStripSlashes($row['caption']);
	$name = substr(basename($row['path']), 0, strrpos(basename($row['path']), '.'));
	$cap_or_name = (!empty($caption)) ? $caption : $name;
?>
	<a href="<?php echo generate_url('picture', $id); ?>" title="<?php echo addcslashes($cap_or_name, '"') ?>"><img class="latest-images-thumbnail" src="<?php echo generate_thumb($path, $id); ?>" alt="<?php echo $name ?>" /></a><br />
	<p class="latest-images-caption"><?php echo $caption ?></p>
<?php } ?>