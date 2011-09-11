<?php

if (basename($_SERVER['PHP_SELF']) == basename( __FILE__ )) {
	// ignorance is bliss
	exit();
}

function parse_tags($str) {
	// 1. Compress all extra whitespaces
	$str = preg_replace('/\Ts{2,}/', ' ', $str);
	// 2. Extract any phrases in quotes
	preg_match_all('/(\'|")(.*?)(\1)/', $str, $phrases);
	// 3. Now remove the phrases from the string
	$str = preg_replace('/(\'|")(.*?)(\1)/', '', $str);
	// 4. Get rid of whitespaces at the end that may have been left
	$str = trim($str);
	// 5. Get single words
	$words = preg_split('/\s+/', $str);
	// 6. Merge single words and phrases
	$tags = array_merge($phrases[2], $words);

	return $tags;
}

function urlify_tag($tag) {
	// 1. Format the incoming tag to use in a URL
	return rawurlencode($tag);
}

function get_picture_tags($picture_id) {
	global $config;
	global $TABLE_PREFIX;
	$picture_id = intval($picture_id);
	$picture_tags = array();

	// TODO: Evaluate whether this method should return the same format for the tags (so in this method it would return an array of arrays, each containing 'id', 'tag' and 'urlified').

	$query = 'SELECT `t2p`.`tag_id`, `t`.`urlified`, `t`.`tag` FROM `'.$TABLE_PREFIX.'tag2picture` as `t2p`, `'.$TABLE_PREFIX.'tags` as `t` WHERE `picture_id` = '.$picture_id.' AND `t2p`.`tag_id` = `t`.`id`;';
	$result = mysql_query($query);
	while($tag_row = mysql_fetch_assoc($result)) {
		$picture_tags[$tag_row['urlified']] = $tag_row['tag_id'];
	}
	return $picture_tags;
}

function delete_picture_tags($picture_id) {
	global $TABLE_PREFIX;
	$picture_id = intval($picture_id);
	$sql = 'DELETE FROM '.$TABLE_PREFIX.'tag2picture WHERE picture_id = '.$picture_id;
	mysql_query($sql);
}

function get_tag_by_name($tag) {
	global $TABLE_PREFIX;
	$existing_tag = array();

	$query = 'SELECT `id`, `tag`, `urlified` FROM `'.$TABLE_PREFIX.'tags` WHERE `tag`="'.$tag.'"';
	$result = run_query($query);
	$row = mysql_fetch_assoc($result);

	if (!is_array($row)) {
		return NULL;
	}
	return array('id' => $row['id'], 'tag' => $row['tag'], 'urlified' => $row['urlified']);
}

function get_tag_by_id($tag_id) {
	global $TABLE_PREFIX;
	$existing_tag = array();
	$tag_id = intval($tag_id);

	$query = 'SELECT `id`, `tag`, `urlified` FROM `'.$TABLE_PREFIX.'tags` WHERE `id`='.$tag_id;
	$result = run_query($query);
	$row = mysql_fetch_assoc($result);

	if (!is_array($row)) {
		return NULL;
	}
	return array('id' => $row['tag_id'], 'tag' => $row['tag'], 'urlified' => $row['urlified']);
}

function get_popular_tags($limit=NULL) {
	global $TABLE_PREFIX;
	// Return a list of the $limit most popular tags
	$query = 'SELECT `t2p`.`tag_id`, COUNT(`t2p`.`tag_id`) AS `popularity`, `t`.`tag`, `t`.`urlified` FROM `'.$TABLE_PREFIX.'tag2picture` AS `t2p`, `'.$TABLE_PREFIX.'tags` AS `t` WHERE `t`.`id`=`t2p`.`tag_id` GROUP BY `t2p`.`tag_id` ORDER BY `popularity` DESC';
	if( isset($limit) ) {
		$limit = intval($limit);
		$query .= ' LIMIT '.$limit;
	}
}

function insert_tag($tag) {
	global $TABLE_PREFIX;
	$urlified = mysql_real_escape_string(urlify_tag($tag));
	$sql = 'INSERT INTO '.$TABLE_PREFIX.'tags (`tag`,`tagdate`,`urlified`)
	VALUES ("'.mysql_real_escape_string($tag).'", NOW(), "'.$urlified.'")';
	if( mysql_query($sql) ) {
		return mysql_insert_id();
	}
}

function add_picture_tags($picture_id, $tags) {
	global $TABLE_PREFIX;
	$tags = parse_tags($tags);
	$picture_id = intval($picture_id);

	/* Process any tags for the picture */
	$existing_tags = $existing_rels = array();
	if (sizeof($tags) > 0) {
		$tagsql = join('", "', $tags);
		$sql = 'SELECT * FROM '.$TABLE_PREFIX.'tags WHERE `tag` IN ("'.$tagsql.'")';
		$result = mysql_query($sql);
		while($tag_row = mysql_fetch_assoc($result)) {
			$existing_tags[$tag_row['tag']] = $tag_row['id'];
		}

		$sql = 'SELECT * FROM '.$TABLE_PREFIX.'tag2picture WHERE `picture_id` ="'.$picture_id.'"';
		$result = mysql_query($sql);
		while($tag_row = mysql_fetch_assoc($result)) {
			$existing_rels[$tag_row['tag_id']] = $tag_row['picture_id'];
		}
	}

	$added_tag_ids = array();
	foreach($tags as $tag) {
		if (!isset($existing_tags[$tag])) {
			// Must be a new tag, register it
			$existing_tags[$tag] = insert_tag($tag);
			$added_tag_ids[] = $existing_tags[$tag];
		}

		if (!isset($existing_rels[$existing_tags[$tag]])) {
			// No connection between tag and picture? create if
			$sql = 'INSERT INTO '.$TABLE_PREFIX.'tag2picture (`picture_id`,`tag_id`,`tagdate`)
			VALUES ("'.$picture_id.'", "'.$existing_tags[$tag].'", NOW())';
			mysql_query($sql);
		}
	}

	// Make sure that adding the tags 'onetwo' and 'one two' doesn't produce conflicts!
	return $added_tag_ids;
}

function delete_tags($tag_ids) {
	global $TABLE_PREFIX;
	$tagsql = join(', ', $tag_ids);
	$sql = 'DELETE FROM '.$TABLE_PREFIX.'tag2picture WHERE tag_id IN ('.$tagsql.')';
	mysql_query($sql);
	$sql = 'DELETE FROM '.$TABLE_PREFIX.'tags WHERE id IN ('.$tagsql.')';
	mysql_query($sql);
}

function remove_picture_tags($picture_id, $tag_ids) {
	// 1. Remove the specified tags from the specified picture.
}

function rename_picture_tag($tag_id, $new_name, $change_urlified=true) {
	// 1. Rename the specified tag and update 'urlified' only if specified.
}

function purge_unused_tags() {
	// 1. Remove all tags that are not associated with any pictures.
}

function update_picture_tags($picture_id, $tags) {
	global $config;
	global $TABLE_PREFIX;
	$tags = parse_tags($tags);
	$picture_id = intval($picture_id);

	/* Process any tags for the picture */
	$existing_tags = $existing_rels = array();
	if (sizeof($tags) > 0) {
		$tagsql = join('", "', $tags);
		$sql = 'SELECT * FROM '.$TABLE_PREFIX.'tags WHERE `tag` IN ("'.$tagsql.'")';
		$result = mysql_query($sql);
		while($tag_row = mysql_fetch_assoc($result)) {
			$existing_tags[$tag_row['tag']] = $tag_row['id'];
		}

		$sql = 'SELECT * FROM '.$TABLE_PREFIX.'tag2picture WHERE `picture_id` ="'.$picture_id.'"';
		$result = mysql_query($sql);
		while($tag_row = mysql_fetch_assoc($result)) {
			$existing_rels[$tag_row['tag_id']] = $tag_row['picture_id'];
		}
	}

	foreach($tags as $tag) {
		if (!isset($existing_tags[$tag])) {
			// Must be a new tag, register it
			$path = mysql_real_escape_string(preg_replace("/[^\w|\.|'|\-|\[|\]]/", "_", $tag));
			$sql = 'INSERT INTO '.$TABLE_PREFIX.'tags (`tag`, `tagdate`, `path`)
			VALUES ("'.mysql_real_escape_string($tag).'", "'.$path.'", NOW())';
			print $sql;
			$result = mysql_query($sql);
			$existing_tags[$tag] = mysql_insert_id();
		}

		if (!isset($existing_rels[$existing_tags[$tag]])) {
			// No connection between tag and picture? create if
			$sql = 'INSERT INTO '.$TABLE_PREFIX.'tag2picture (`picture_id`, `tag_id`, `tagdate`)
			VALUES ("'.$picture_id.'", "'.$existing_tags[$tag].'", NOW())';
			mysql_query($sql);
		}
	}

	// Now remove links to any tags that have been deleted
	foreach($existing_rels as $tag_id => $pic_id) {
		if (!in_array($tag_id,$existing_tags)) {
			$sql = "DELETE FROM `".$TABLE_PREFIX."tag2picture`
			WHERE `picture_id` = '$picture_id' AND `tag_id` = '$tag_id'";
			mysql_query($sql);
		}
	}

}

?>