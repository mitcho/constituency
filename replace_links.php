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

// Pick out the data from the database
$limit = $start && $end ? "entry >= $start and entry <= $end and " : '';
$result = mysql_query("SELECT l.id, l.entry, l.text FROM " . LINKS_TABLE . " as l join " . ENTRIES_TABLE . " as e on l.entry = e.id where {$limit} length(e.stanford) > 0 and e.processed is not null and e.stanford not regexp '^SENTENCE_SKIPPED_OR_UNPARSABLE' ORDER BY entry asc, id asc");

$lastEntry = 0;
$badEntryList = array();
while ($link = mysql_fetch_array($result)) {
	// For each row in the retrieved data...
	$id = $link["id"];
	$entry = $link["entry"];
	
	if (array_search($entry, $badEntryList) !== false)
		continue;
	
	$text = trim($link["text"]);
	$text = prepHTMLForParser($text);

	// Get the corresponding tree
	$tree = getTree($entry);
	
	echo "Link " . strval($entry) . "+" . strval($id) . "$break";
	echo "\t$text$break";

	// Strip out link delimiters if this is the first time we've seen this entry
	if ($lastEntry != $entry) {
		$tree = str_replace("<<<LINK", "", $tree);
		$tree = str_replace("\nLINK;", "", $tree);
	}

	$newTree = addLINKs($tree, $text);

	if ($newTree) {
		// Update the database
		echo "new:\n$newTree\n";
		setTree($entry, $newTree);
		// echo "\t" . $tree . "\n\n";
		echo "updated tree with LINK.$break";
	} else {
		echo "didn't update tree with LINK$break";
	}
	
	if (isset($updatedColumns))
		echo "result: $updatedColumns$break";
	
	echo "$split";

	$lastEntry = $entry;
}