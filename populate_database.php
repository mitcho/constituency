<?php
// Scrapes Metafilter.com and pushes stuff into the database.

$web = isset($_SERVER['SERVER_PROTOCOL']) &&
			 $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1';
$break = $web ? '<br/>' : "\n";
$split = $web ? '<hr/>' : "-----\n";

include('functions.php');
include("connect_mysql.php");

define("ENTRIES_TABLE", "entries");
define("LINKS_TABLE", "links");

$start = 1;
$end = 10;
if (isset($_GET['start']))
	$start = $_GET['start'];
if (isset($_SERVER['argv'][1]))
	$start = $_SERVER['argv'][1];
if (isset($_GET['end']))
	$end = $_GET['end'];
if (isset($_SERVER['argv'][2]))
	$end = $_SERVER['argv'][2];

for ($id = $start; $id < $end; $id++) {
	echo "$id{$break}";
	
	// Pull out all of the data, if any
	$data = getEntryData(getEntry($id));

	if (sizeof($data) != 3) {
		echo "\thas no data, size = " . strval(sizeof($data)) . $break . $split;
		continue;
	}
	
	// Check if it's already in the database, skip.
	if ( $db->get_var('select id from ' . ENTRIES_TABLE . " where id = $id") ) {
		echo "\talready in database, skipping" . $break . $split;
		continue;
	}
	
	echo "\thas data{$break}";
		
	// Push entry into the database
	$db->insert(ENTRIES_TABLE, array(
		'id' => (int) $id,
		'author' => (int) $data[2],
		'content' => $data[1]
	), array('%d', '%d', '%s'));

	// Pull out the links
	$linkData = getLinkData($data[1]);
	foreach ( $linkData as $lid => $link ) {
		$link = $linkData[$lid];
		$db->insert(LINKS_TABLE, array(
			'id' => $lid,
			'entry' => $id,
			'href' => $link[1],
			'text' => $link[2]
		), array('%d', '%d', '%s', '%s'));
	}
	
	echo "\thas " . count($linkData) . " links{$break}{$split}";
}

/**
 * Initializes a cURL session and sets the URL and the session to return the
 * retrieved data
 */
function getEntry($id) {
	return file_get_contents("http://www.metafilter.com/$id");
}

function getEntryData($text) {
	$data = array();
	
	// Finds the content and the user's id.
	preg_match('!<div class="copy">(.*?)<span class="smallcopy">posted by <a href="http://www\.metafilter\.com/user/(\d+)" target="_self">!sm', $text, $data);
	
	return $data;
}

function getLinkData($text) {
	$links = array();
	
	preg_match_all('!<a[^>]+?href=[\'"](.*?)[\'"][^>]*?>(.*?)<\/a>!im', $text, $links, PREG_SET_ORDER);
	
	return $links;
}
