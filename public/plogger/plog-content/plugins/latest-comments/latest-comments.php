<?php
/*
Addon Name: Latest Comments
Description: This script allows you to include a specified number of the latest comments from your Plogger gallery on your website.
Version: 1.0
Author: Kim Parsell and Mike Conover
Author URI: http://plogger.org/
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
// $plog_lc_site_url = 'http://www.yoursite.com/plogger/embedded.php';
$plog_lc_site_url = $config['gallery_url'];

/* How many comments do you want to show? */
$plog_lc_amount = '5';

/* Trim the comment length? Set to '0' for no trimming */
$plog_lc_comment_trim = '100';

/* The database query to pull the latest comments from the database */
$plog_lc_query = "SELECT * FROM ".PLOGGER_TABLE_PREFIX."comments WHERE `approved` = 1 ORDER BY `id` DESC LIMIT $plog_lc_amount";

$plog_lc_result = mysql_query($plog_lc_query) or die ("Could not execute query: $plog_lc_query." .mysql_error());

/* Start html output */

if (mysql_num_rows($plog_lc_result) > 0) {
	echo "\n\t" . '<ul class="latest-comments">';

$config['baseurl'] = $plog_lc_site_url;

/* The latest comments loop */
	while ($row = mysql_fetch_array($plog_lc_result)) {
		$id = $row['id'];
		$parent_id = $row['parent_id'];
		$author = $row['author'];
		$date = $row['date'];
		$comment_length = intval($plog_lc_comment_trim);
		$comment = ($comment_length !== 0 && $comment_length < strlen($row['comment'])) ? substr($row['comment'], 0, intval($plog_lc_comment_trim)).' ...' : $row['comment'];
		$number = strrpos($path, '/');
		$number = $number+1;
		$url = substr($path, $number);
		$plog_lc_picture = get_picture_by_id($parent_id);
		$cap_or_name = (!empty($plog_lc_picture['caption'])) ? stripslashes($plog_lc_picture['caption']) : ucfirst(substr(basename($plog_lc_picture['path']), 0, strrpos(basename($plog_lc_picture['path']), '.')));

		$plog_lc_comment = '<strong>'.$author.'</strong> on <a title="'.addcslashes($cap_or_name, '"').'" href="'.generate_url('picture', $plog_lc_picture['id']).'"><strong>'.$cap_or_name.'</strong></a><br />'.$comment;

/* List the comments */
	echo "\n\t\t" . '<li class="latest-comments">'.$plog_lc_comment.'</li>';
	}
/* End latest comments loop */

	echo "\n\t" . '</ul>' ."\n";
/* End html output */

} else {
	echo "\n\t" . '<p>'.plog_tr('No comments yet').'</p>' . "\n";
}

?>