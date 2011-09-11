<?php

/* This file handles the generation of the RSS feed. */

include_once(dirname(__FILE__).'/plog-load-config.php');

function generate_RSS_feed ($level, $id, $search = '') {
	global $config;

	$config['feed_title'] = SmartStripSlashes($config['feed_title']);

	// remove plog-rss from the end, if present .. is there a better way to determine the full url?
	$is_rss = strpos($config['baseurl'], 'plog-rss.php');
	if ($is_rss !== false) {
		$config['baseurl'] = substr($config['baseurl'], 0, $is_rss);
	}

	if (!empty($search)) $level = 'search';

	// Aggregate feed of all albums with collection specified by id
	if ($level == 'collection') {
		if ($config['feed_content'] == 0 ) {
			plogger_init_albums(array(
			'collection_id' => $id,
			'sortby' => 'id',
			'sortdir' => 'DESC',
			'from' => 0,
			'limit' => $config['feed_num_entries']
			));
		} else {
			plogger_init_pictures(array(
			'type' => 'collection',
			'value' => $id,
			'limit' => $config['feed_num_entries'],
			'sortby' => 'id',
			));
		}
		$collection = get_collection_by_id($id);
		$config['feed_title'] .= ': '.$collection['name'];

	} else if ($level == 'album') {
		plogger_init_pictures(array(
		'type' => 'album',
		'value' => $id,
		'limit' => $config['feed_num_entries'],
		'sortby' => 'id',
		));
		$album = get_album_by_id($id);
		$config['feed_title'] .= ': '.$album['album_name'];

	} else if ($level == 'picture') {
		plogger_init_picture(array(
		'id' => $id,
		'comments' => 'DESC'
		));
		$picture = get_picture_by_id($id);
		$config['feed_title'] .= ': '.basename($picture['path']);

	} else if ($level == 'search') {
		plogger_init_search(array(
		'searchterms' => $search,
		'limit' => $config['feed_num_entries'],
		));

	} else if (($level == 'collections') or ($level == '')) {
		plogger_init_albums(array(
		'collection_id' => -1,
		'sortby' => 'id',
		'sortdir' => 'DESC',
		'from' => 0,
		'limit' => $config['feed_num_entries']
		));
		$config['feed_title'] .= ': '.plog_tr('Entire Gallery');
	}

	// generate RSS header
	$rssFeed = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	$rssFeed.= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:media=\"http://search.yahoo.com/mrss/\">\n";

	$rssFeed.= "<channel>\n";
	$rssFeed.= "<title>".$config['feed_title']."</title>\n";
	$rssFeed.= "<description>".plog_tr('Plogger RSS Feed')."</description>\n";
	$rssFeed.= "<language>".$GLOBALS['locale']."</language>\n";
	$rssFeed.= "<docs>http://blogs.law.harvard.edu/tech/rss</docs>\n";
	$rssFeed.= "<generator>Plogger</generator>\n";
	$rssFeed.= "<link>".$config['gallery_url']."</link>\n";
	$rssFeed.= "<atom:link href=\"http://".$_SERVER['HTTP_HOST'].str_replace('&', '&amp;', $_SERVER['REQUEST_URI'])."\" rel=\"self\" type=\"application/rss+xml\" />\n";

	$header = 1;

	while(plogger_has_albums()) {
		plogger_load_album();

		if ($header) {
			$submitdate = date('D, d M Y H:i:s O');

			$rssFeed.= "<pubDate>".$submitdate."</pubDate>\n";
			$rssFeed.= "<lastBuildDate>".$submitdate."</lastBuildDate>\n";
			$header = 0;
		}

		$sql = "SELECT UNIX_TIMESTAMP(`date_modified`) AS `pubdate` FROM `".PLOGGER_TABLE_PREFIX."pictures` WHERE `parent_album` = '".plogger_get_album_id()."' ORDER BY `date_modified` DESC LIMIT 1";
		$result = run_query($sql);
		$row = mysql_fetch_assoc($result);
		$pubdate = date('D, d M Y H:i:s O', $row['pubdate']);

		$title = plogger_get_album_name();
		$num = plogger_album_picture_count();
		$num_pictures = ($num == 1) ? plog_tr('image') : plog_tr('images');
		$pagelink = plogger_get_album_url();
		$thumbpath = str_replace(array('%2F', '%3A'), array('/', ':'), rawurlencode(plogger_get_album_thumb()));
		$descript = '&lt;p&gt;&lt;a href="'.$pagelink.'"
		title="'.$title.'"&gt;
		&lt;img src="'.$thumbpath.'" alt="'.$title.'" style="border: 2px solid #000;" /&gt;
		&lt;/a&gt;&lt;/p&gt;&lt;p&gt;'.$title.' ('.$num.' '.$num_pictures.')&lt;/p&gt;&lt;p&gt;'.htmlspecialchars(plogger_get_album_description()).'&lt;/p&gt;';

		$rssFeed .= "<item>\n";
		$rssFeed .= "\t<pubDate>".$pubdate."</pubDate>\n";
		$rssFeed .= "\t<title>".$title."</title>\n";
		$rssFeed .= "\t<link>".$pagelink."</link>\n";
		$rssFeed .= "\t<description>".$descript."</description>\n";
		$rssFeed .= "\t<guid isPermaLink=\"false\">".$thumbpath."</guid>\n";
		$rssFeed .= "\t<media:content url=\"".$thumbpath."\" type=\"image/jpeg\" />\n";
		$rssFeed .= "\t<media:title>".$title."</media:title>\n";
		$rssFeed .= "</item>\n";

	}

	while(plogger_has_pictures()) {
		plogger_load_picture();
		// If at picture level, check to make sure it has comments first
		if ($level != 'picture' || plogger_picture_has_comments()) {

			if ($header) {
				$submitdate = plogger_get_picture_date('D, d M Y H:i:s O', 1);
				$takendate = plogger_get_picture_date();

				$rssFeed.= "<pubDate>".$submitdate."</pubDate>\n";
				$rssFeed.= "<lastBuildDate>".$submitdate."</lastBuildDate>\n";
				$header = 0;
			}

			$rssFeed .= "<item>\n";
			if ($config['allow_fullpic']) {
				$urlPath = str_replace(array('%2F', '%3A'), array('/', ':'), rawurlencode(plogger_get_source_picture_url()));
			} else {
				$urlPath = str_replace(array('%2F', '%3A'), array('/', ':'), rawurlencode(plogger_get_picture_thumb(THUMB_LARGE)));
			}

			$caption = plogger_get_picture_caption();
			$thumbpath = plogger_get_picture_thumb(THUMB_RSS);

			$pagelink = plogger_get_picture_url();

			if ($caption == '' || $caption == '&nbsp;') $caption = plog_tr('New Image (no caption)');
			$caption .= ' - '.$takendate;

			$descript = '&lt;p&gt;&lt;a href="'.$pagelink.'"
			title="'.$caption.'"&gt;
			&lt;img src="'.$thumbpath.'" alt="'.$caption.'" style="border: 2px solid #000;" /&gt;
			&lt;/a&gt;&lt;/p&gt;&lt;p&gt;'.$caption.'&lt;/p&gt;';

			$descript .= '&lt;p&gt;'.htmlspecialchars(plogger_get_picture_description()).'&lt;/p&gt;';

			$rssFeed .= "\t<pubDate>".$submitdate."</pubDate>\n";
			$rssFeed .= "\t<title>".$caption."</title>\n";
			$rssFeed .= "\t<link>".$pagelink."</link>\n";
			$rssFeed .= "\t<description>".$descript."</description>\n";
			$rssFeed .= "\t<guid isPermaLink=\"false\">".$thumbpath."</guid>\n";
			$rssFeed .= "\t<media:content url=\"".$urlPath."\" type=\"image/jpeg\" />\n";
			$rssFeed .= "\t<media:title>".$caption."</media:title>\n";
			$rssFeed .= "</item>\n";
			if ($level == 'picture') {
				while(plogger_picture_has_comments()) {
					plogger_load_comment();
					$rssFeed .= "<item>\n";
					$rssFeed .= "\t<pubDate>".plogger_get_comment_date('D, d M Y H:i:s O')."</pubDate>\n";
					$rssFeed .= "\t<title>Comment by ".plogger_get_comment_author()."</title>\n";
					$rssFeed .= "\t<link>".$pagelink."</link>\n";
					$rssFeed .= "\t<description>".plogger_get_comment_text()."</description>\n";
					$rssFeed .= "\t<guid isPermaLink=\"true\">".$pagelink."#Comment-".plogger_get_comment_id()."</guid>\n";
					$rssFeed .= "</item>\n";
				}
			}
		}
	}

	$rssFeed .= "</channel>\n</rss>";
	echo $rssFeed;
}

// Send proper header
header('Content-Type: application/xml');

$level = isset($_GET['level']) ? $_GET['level'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : '0';

// Process path here - is set if mod_rewrite is in use

// Some Estonian remarks were here
if (!empty($_REQUEST['path'])) {
	// The following line calculates the path in the album and excludes any subdirectories if
	// Plogger is installed in one
	$path = join('/', array_diff(explode('/', $_SERVER['REQUEST_URI']), explode('/', $_SERVER['PHP_SELF'])));
	$resolved_path = resolve_path($path);
	$level = (isset($resolved_path['level'])) ? $resolved_path['level'] : 'collections';
	$id = (isset($resolved_path['id'])) ? $resolved_path['id'] : 0;
}

$parts = parse_url($_SERVER['REQUEST_URI']);

if (isset($parts['query'])) {
	parse_str($parts['query'], $query_parts);
}

if (isset($query_parts['searchterms'])) {
	generate_RSS_feed($level, $id, $query_parts['searchterms']);
} else {
	generate_RSS_feed($level, $id);
}

close_db();

?>