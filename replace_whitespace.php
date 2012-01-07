<?php

// Replace the <<<LINK...\nLINK; annotations in the db

// Provides common judgement functions
include("functions.php");
// Connect to the database, provides $session and $success
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

$web = isset($_SERVER['SERVER_PROTOCOL']) &&
       $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1';
$break = $web ? '<br/>' : "\n";
$split = $web ? '<hr/>' : "-----\n";

$start = false;
$end = false;
if (isset($_GET['start']))
	$start = $_GET['start'];
if (isset($_SERVER['argv'][1]))
	$start = $_SERVER['argv'][1];
if (isset($_GET['end']))
	$end = $_GET['end'];
if (isset($_SERVER['argv'][2]))
	$end = $_SERVER['argv'][2];

// Pick out the parsed entries from the database
$limit = $start && $end ? "id >= $start and id <= $end and " : '';
$result = mysql_query("SELECT id, stanford FROM " . ENTRIES_TABLE . " where {$limit} length(stanford) > 0 and processed is not null and stanford not regexp '^SENTENCE_SKIPPED_OR_UNPARSABLE' ORDER BY id asc");

while ($link = mysql_fetch_array($result)) {
	// For each row in the retrieved data...
	$parse = $link["stanford"];
	$id = $link["id"];
	
	// echo $parse;
	$newParse = oneParsePerLine($parse);
	// echo $newParse;

	if ($newParse != $parse && $newParse !== false && strlen($newParse) > 0) {
		// Update the database
		setTree($id, $newParse);
		echo "updated tree for id #{$id}.$break";
	}
}