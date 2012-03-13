<?php

include_once('../functions.php');
include_once('../search_functions.php');

// get table consts set up
if(!isset($argv))
	$argv = array();
$args = parseArgs($argv);
extract($args);

// debug global
$debug = ($_ENV['DEBUG'] || isset($_GET['debug']));

function searchAndReturnBackrefs( $query ) {
	global $debug, $range;
	
	$pattern = new Pattern($query);
	
	if ($debug)
		$range = array(0, 1000);

	$results = $pattern->queryDb($range);
	return $pattern->backrefs;
}

function getTagId( $tag ) {
	$results = mysql_query( "select id from tags where name = '$tag'" );
	if ( $row = mysql_fetch_array($results) ) {
		return $row['id'];
	} else {
		mysql_query( "insert into tags (name) values ('$tag')" );
		return mysql_insert_id();
	}
}

// ids - (mixed) can be an array of strings or a single string of the form "ENTRYID:LINKID".
//       if it's just a single number, it's interpreted as the entire entry, i.e. link = -1.
// label - (string) 
function annotate( $ids, $tag ) {
	$tag_id = getTagId($tag);
	
	if ( is_string( $ids ) ) {
		$ids = array( (int) $ids );
	}
	
	$values = array();
	
	foreach ($ids as $id) {
		$split = split(':', $id);
		if (count($split) == 0)
			$values[] = "($tag_id, $id, -1)";
		else
			$values[] = "($tag_id, $split[0], $split[1])";
	}
	
	mysql_query( "insert into " . TAGS_TABLE . " (tid, entry, id) values " . implode(',', $values) . ' on duplicate key update tid = tid' );
}

function getEntryLinkID( $entry, $linkText ) {
	$entry = (int) $entry;

	$linkText = preg_replace('/\([A-Z$]+/', '', $linkText);
	$linkText = str_replace(')', '', $linkText);
	// in case punctuation etc. was split into separate nodes, space would be added, so allow wildcard in between.
	$linkText = preg_replace('/\s+/', '%', $linkText);

	$results = mysql_query("select entry, id from " . LINKS_TABLE . " where text like '" . mysql_escape_string($linkText) . "' and entry = {$entry}");
	if ($row = mysql_fetch_array($results))
		return $row['entry'] . ':' . $row['id'];

	return false;
}