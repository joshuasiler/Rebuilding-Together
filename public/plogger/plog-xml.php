<?php

error_reporting(E_ALL);

// Sometimes, it can take very long
set_time_limit(30);

// plog-xml.php
// Generates Plogger content in XML for alternative interfaces.

require(dirname(__FILE__).'/plog-load-config.php');

header('Content-Type: text/xml');

// Put the config information at the top of the XML file
// if noconfig option is not specified
$xml = '<?xml version="1.0" standalone="yes" ?>';

$xml .= '<plogger name="'.htmlspecialchars($config['gallery_name']).'">';

if (!isset($_GET['noconfig']) || (!$_GET['noconfig'])) {
	$xml .= '<config>';

	// Print out the config vars programmatically so that any future additions
	// automatically get brought into the XML

	// Unset any sensitive config information

	unset($config['admin_username']);
	unset($config['admin_password']);
	unset($config['admin_email']);
	unset($config['basedir']);

	foreach ($config as $var => $val) {
		$xml .= '<'.$var.'>'.$val.'</'.$var.'>';
	}

	$xml .= '</config>';
}

// Now comes the fun
// There are 10 arguments that this file takes
//
// collections
// albums
// pictures
// comments
// all
// collection_id
// album_id
// picture_id
// comment_id
// limit

// all=1 is the same as collections=1&albums=1&pictures=1&comments=1
if (isset($_GET['all']) && ($_GET['all'] == 1)) {
	$_GET['collections'] = 1;
	$_GET['albums'] = 1;
	$_GET['pictures'] = 1;
	$_GET['comments'] = 1;
}

if (isset($_GET['limit'])) {
	$limit = intval($_GET['limit']);
	$limitType = '';
} else {
	$limit = 0;
	$limitType = '';
}

// If any *_id arguments are set, the level arguments for that level and above
// must be unset to avoid showing the levels that do not eventually contain
// that *_id

if (isset($_GET['comment_id'])) {
	unset($_GET['collections']);
	unset($_GET['albums']);
	unset($_GET['pictures']);
	unset($_GET['comments']);

	$limit = 0;
	$limitType = '';
} else if (isset($_GET['picture_id'])) {
	unset($_GET['collections']);
	unset($_GET['albums']);
	unset($_GET['pictures']);

	if ($limit > 0) {
		if (!empty($_GET['comments'])) {
			$limitType = 'comments';
		} else {
			$limitType = '';
			$limit = 0;
		}
	}
} else if (isset($_GET['album_id'])) {
	unset($_GET['collections']);
	unset($_GET['albums']);

	if ($limit > 0) {
		if (!empty($_GET['pictures'])) {
			$limitType = 'pictures';
		} else if (!empty($_GET['comments'])) {
			$limitType = 'comments';
		} else {
			$limitType = '';
			$limit = 0;
		}
	}
} else if (isset($_GET['collection_id'])) {
	unset($_GET['collections']);

	if ($limit > 0) {
		if (!empty($_GET['albums'])) {
			$limitType = 'albums';
		} else if (!empty($_GET['pictures'])) {
			$limitType = 'pictures';
		} else if (!empty($_GET['comments'])) {
			$limitType = 'comments';
		} else {
			$limitType = '';
			$limit = 0;
		}
	}
}

if (($limit > 0) && ($limitType == '')) {
	if ($limit > 0) {
		if (!empty($_GET['collections'])) {
			$limitType = 'collections';
		} else if (!empty($_GET['albums'])) {
			$limitType = 'albums';
		} else if (!empty($_GET['pictures'])) {
			$limitType = 'pictures';
		} else if (!empty($_GET['comments'])) {
			$limitType = 'comments';
		} else {
			$limit = 0;
			$limitType = '';
		}
	}
}

if (isset($_GET['collections']) || isset($_GET['albums']) || isset($_GET['pictures']) || isset($_GET['comments']) || isset($_GET['collection_id']) || isset($_GET['album_id']) || isset($_GET['picture_id']) || isset($_GET['comment_id'])) {
	if (isset($_GET['collection_id'])) {
		$collections = array(get_collection_by_id($_GET['collection_id']));
	} else if (isset($_GET['collections'])) {
		$collections = get_collections('mod', 'DESC');
	} else if (isset($_GET['albums']) || isset($_GET['album_id']) || isset($_GET['pictures']) || isset($_GET['picture_id']) || isset($_GET['comments']) || isset($_GET['comment_id'])) {
		$collections = get_collection_ids('mod', 'DESC');
	} else {
		$collections = array();
	}

	// $total counts the number of items that are being limited that have been returned.
	// When total equals the limit, we're done.
	$total = 0;

	foreach ($collections as $collection) {
		// A collection's tag is only shown if all collections are being shown
		// or if this specific collection was specified by collection_id
		if ((isset($_GET['collections']) && ($_GET['collections'] == 1)) || (isset($_GET['collection_id']) && ($_GET['collection_id'] == $collection['id']))) {
			if ($limitType == 'collections') {
				if ($total == $limit) {
					break;
				}

				$total++;
			}

			$collection['thumb_path'] = 'plog-thumb.php?id='.$collection['thumbnail_id'];

			$xml .= '<collection';
			// Put together the tag attributes
			foreach ($collection as $var => $val) {
				$xml .= ' '.$var.'="'.htmlspecialchars($val).'"';
			}
			$xml .= '>';
		}

		if (isset($_GET['albums']) || isset($_GET['album_id'])) {
			$albums = get_albums($collection['id'], 'mod', 'DESC');
		} else if (isset($_GET['pictures']) || isset($_GET['picture_id']) || isset($_GET['comments']) || isset($_GET['comment_id'])) {
			$albums = get_album_ids($collection['id'], 'mod', 'DESC');
		} else {
			$albums = array();
		}

		foreach ($albums as $album) {
			if ((isset($_GET['collection_id']) && ($_GET['collection_id'] == $album['collection_id'])) || !isset($_GET['collection_id'])) {
				if ((isset($_GET['albums']) && ($_GET['albums'] == 1)) || (isset($_GET['album_id']) && ($_GET['album_id'] == $album['album_id']))) {
					if ($limitType == 'albums') {
						if ($total == $limit) {
							break;
						}

						$total++;
					}

					$album['album_path'] = sanitize_filename($album['collection_name']).'/'.sanitize_filename($album['album_name']).'/';
					$album['thumb_path'] = 'plog-thumb.php?id='.$album['thumbnail_id'];

					$xml .= '<album';
					foreach ($album as $var => $val) {
						$xml .= ' '.$var.'="'.htmlspecialchars($val).'"';
					}
					$xml .= '>';
				}

				if (isset($_GET['pictures'])) {
					$pictures = get_pictures($album['album_id'], 'mod', 'DESC');
				} else if (isset($_GET['picture_id'])) {
					$pic = get_picture_by_id($_GET['picture_id'], $album['album_id']);

					if ($pic) {
						$pictures = array($pic);
					} else {
						$pictures = array();
					}
				} else if (isset($_GET['comments']) || isset($_GET['comment_id'])) {
					$pictures = get_picture_ids($album['album_id'], 'mod', 'DESC');
				} else {
					$pictures = array();
				}

				foreach ($pictures as $picture) {
					if ((isset($_GET['album_id']) && ($_GET['album_id'] == $picture['parent_album'])) || !isset($_GET['album_id'])) {
						if ((isset($_GET['pictures']) && ($_GET['pictures'] == 1)) || (isset($_GET['picture_id']) && ($_GET['picture_id'] == $picture['id']))) {
							if ($limitType == 'pictures') {
								if ($total == $limit) {
									break;
								}

								$total++;
							}

							$picture['sm_thumb_path'] = 'plog-thumb.php?id='.$picture['id'];
							$picture['lg_thumb_path'] = 'plog-thumb.php?id='.$picture['id'].'&type=2';

							$xml .= '<picture';
							foreach ($picture as $var => $val) {
								$xml .= ' '.$var.'="'.htmlspecialchars($val).'"';
							}
							$xml .= '>';

						}

						if (isset($_GET['comments']) || isset($_GET['comment_id'])) {
							$comments = plogger_get_comments($picture['id'], 'mod', 'DESC');

							foreach ($comments as $comment) {
								if ((isset($_GET['picture_id']) && ($_GET['picture_id'] == $comment['parent_id'])) || !isset($_GET['picture_id'])) {
									if ((isset($_GET['comments']) && ($_GET['comments'] == 1)) || (isset($_GET['comment_id']) && ($_GET['comment_id'] == $comment['id']))) {
										if ($limitType == 'comments') {
											if ($total == $limit) {
												break;
											}

											$total++;
										}

										$xml .= '<comment';
										foreach ($comment as $var => $val) {
											$xml .= ' '.$var.'="'.htmlspecialchars($val).'"';
										}
										$xml .= '/>';

									}
								}
							}
						}

						if ((isset($_GET['pictures']) && ($_GET['pictures'] == 1)) || (isset($_GET['picture_id']) && ($_GET['picture_id'] == $picture['id']))) {
							$xml .= '</picture>';
						}
					}
				}

				if ((isset($_GET['albums']) && ($_GET['albums'] == 1)) || (isset($_GET['album_id']) && ($_GET['album_id'] == $album['album_id']))) {
					$xml .= '</album>';
				}
			}
		}

		if ((isset($_GET['collections']) && ($_GET['collections'] == 1)) || (isset($_GET['collection_id']) && ($_GET['collection_id'] == $collection['id']))) {
			$xml .= '</collection>';
		}
	}
}

$xml .= '</plogger>';

echo $xml;
close_db();
exit;

function get_collection_ids($sort = 'alpha', $order = 'DESC') {
	global $config;

	if ($sort == 'mod') {
		$query = "SELECT `c`.`id`
		FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `i`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `i`.`parent_collection`=`c`.`id`
		GROUP BY `i`.`parent_collection`
		ORDER BY `i`.`date_submitted` ";

		if ($order == 'ASC') {
			$query .= ' ASC ';
		} else {
			$query .= ' DESC ';
		}
	} else {
		$query = "SELECT `c`.`id`
		FROM `".PLOGGER_TABLE_PREFIX."collections` AS `c`
		ORDER BY `c`.`name` ";

		if ($order == 'ASC') {
			$query .= ' ASC ';
		} else {
			$query .= ' DESC ';
		}
	}

	$resultCollection = run_query($query);

	$collections = array();

	while ($collection = mysql_fetch_assoc($resultCollection)) {
		$collections[$collection['id']] = $collection;
	}

	return $collections;
}

function get_album_ids($collection_id = null, $sort = 'alpha', $order = 'DESC') {
	global $config;

	$albums = array();

	if ($sort == 'mod') {
		$query = "SELECT `a`.`id` AS `album_id`
		FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `i`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."albums` AS `a` ON `i`.`parent_album`=`a`.`id`";

		if ($collection_id) {
			$query .= " WHERE `i`.`parent_collection`=".intval($collection_id);
		}

		$query .= "
		GROUP BY `i`.`parent_album`
		ORDER BY `i`.`date_submitted` ";

		if ($order == 'ASC') {
			$query .= ' ASC ';
		} else {
			$query .= ' DESC ';
		}
	} else {
		$query = "SELECT `a`.`id` AS `album_id`
		FROM `".PLOGGER_TABLE_PREFIX."albums` AS `a`
		LEFT JOIN `".PLOGGER_TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`";

		if ($collection_id) {
			$query .= " WHERE `c`.id=".intval($collection_id)." ";
		}

		$query .= " ORDER BY `c`.`name` ASC, `a`.`name` ASC";
	}

	$result = run_query($query);

	while ($album = mysql_fetch_assoc($result)) {
		$albums[$album['album_id']] = $album;
	}

	return $albums;
}

function get_picture_ids($album_id, $order = 'alpha', $sort = 'DESC') {
	global $config;

	$query = "SELECT `p`.`id`
	FROM `".PLOGGER_TABLE_PREFIX."pictures` AS `p`
	LEFT JOIN `".PLOGGER_TABLE_PREFIX."albums` AS `a` ON `p`.`parent_album`=`a`.`id`
	WHERE `a`.`id`=".intval($album_id);

	if ($order == 'mod') {
		$query .= " ORDER BY `p`.`date_submitted` ";
	} else {
		$query .= " ORDER BY `p`.`caption` ";
	}

	if ($sort == 'ASC') {
		$query .= ' ASC ';
	} else {
		$query .= ' DESC ';
	}

	$result = run_query($query);

	$pictures = array();

	while ($row = mysql_fetch_assoc($result)) {
		$pictures[$row['id']] = $row;
	}

	return $pictures;
}

?>