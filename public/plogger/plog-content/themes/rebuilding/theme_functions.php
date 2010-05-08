<?php

// Functions to display stats in footer
function plogger_stats_count_total_collections() {
	$query = "SELECT COUNT(*) AS `n` FROM `".PLOGGER_TABLE_PREFIX."collections`";
	$result = run_query($query);
	$num_collections = mysql_result($result, 0, 'n');
	echo $num_collections . ' ';
	echo ($num_collections == 1) ? plog_tr('collection') : plog_tr('collections');
}

function plogger_stats_count_total_albums() {
	$query = "SELECT COUNT(*) AS `n` FROM `".PLOGGER_TABLE_PREFIX."albums`";
	$result = run_query($query);
	$num_albums = mysql_result($result, 0, 'n');
	echo $num_albums . ' ';
	echo ($num_albums == 1) ? plog_tr('album') : plog_tr('albums');
}

function plogger_stats_count_total_pictures() {
	$query = "SELECT COUNT(*) AS `n` FROM `".PLOGGER_TABLE_PREFIX."pictures`";
	$result = run_query($query);
	$num_pictures = mysql_result($result, 0, 'n');
	echo $num_pictures . ' ';
	echo ($num_pictures == 1) ? plog_tr('image') : plog_tr('images');
}

function plogger_stats_count_total_comments() {
	$query = "SELECT COUNT(*) AS `n` FROM `".PLOGGER_TABLE_PREFIX."comments` WHERE approved = 1";
	$result = run_query($query);
	$num_comments = mysql_result($result, 0, 'n');
	echo $num_comments . ' ';
	echo ($num_comments == 1) ? plog_tr('comment') : plog_tr('comments');
}

?>