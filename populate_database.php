<?php
// Scrapes Metafilter.com and pushes stuff into the database.

$web = isset($_SERVER['SERVER_PROTOCOL']) &&
       $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1';
$break = $web ? '<br/>' : "\n";
$split = $web ? '<hr/>' : "-----\n";
include("connect_mysql.php");
if ($_REQUEST['tables']) {
	define("ENTRIES_TABLE", "hyperlinks_entries_" . $_REQUEST['tables']);
	define("LINKS_TABLE", "hyperlinks_links_" . $_REQUEST['tables']);
} else if ($_ENV['CONSTITUENCY_TABLES']) {
	define("ENTRIES_TABLE", "hyperlinks_entries_" . $_ENV['CONSTITUENCY_TABLES']);
	define("LINKS_TABLE", "hyperlinks_links_" . $_ENV['CONSTITUENCY_TABLES']);
} else {
	define("ENTRIES_TABLE", "hyperlinks_entries");
	define("LINKS_TABLE", "hyperlinks_links");
}

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

for($id = $start; $id < $end; $id++) {
	echo "$id{$break}";
	
	// Pull out all of the data, if any
	$data = getEntryData(getEntry($id));

	if (sizeof($data) != 3) {
		echo "\thas no data, size = " . strval(sizeof($data)) . $break;
	} else {
		echo "\thas data{$break}";
		
		// Push entry into the database
		$author = mysql_real_escape_string(trim($data[2]));
		$content = mysql_real_escape_string(trim($data[1]));

		mysql_query("INSERT INTO " . ENTRIES_TABLE . " (id,author,content,modified_by) VALUES ($id,{$author},\"{$content}\",'$user') on duplicate key update author = {$author}, content = \"{$content}\", processed = NULL");
		
		// Pull out the links
		$linkData = getLinkData($data[1]);
		$linkValues = array();
		
		$numberofLinks = sizeof($linkData);
		
    for ($lid = 0; $lid < $numberofLinks; $lid++) {
			$link = $linkData[$lid];
			
			$entry = mysql_real_escape_string($link[1]);
			$href = mysql_real_escape_string($link[2]);
			
			array_push($linkValues, "($lid,$id,\"{$entry}\",\"{$href}\")");
		}
    	
		echo "\thas $lid links{$break}";
		
		if ($lid > 0) {
			mysql_query("INSERT INTO " . LINKS_TABLE . " (id,entry,href,text) VALUES " . implode(",", $linkValues));
		}
	}
	
	echo $split;
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
