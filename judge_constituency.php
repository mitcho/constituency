<?php

// This file runs through all of the stuff in the database and determines their constituency

// Provides common judgement functions
include("functions.php");
// Connect to the database, provides $session and $success
include("connect_mysql.php");

$user = mysql_real_escape_string(get_current_user());

// limit pcre backtrack, in order to aviod segfaults: Issue 16
ini_set("pcre.backtrack_limit", 10000);

$web = isset($_SERVER['SERVER_PROTOCOL']) &&
       $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1';
$break = $web ? '<br/>' : "\n";
$split = $web ? '<hr/>' : "-----\n";

$args = parseArgs($argv);
extract($args);
$start = $range ? $range[0] : false;
$end = $range ? $range[1] : false;

// add the rerun filters
$extra = " and (l.processed is null or l.error = 'missing_parse')";
if ($rerun == 'errors')
	$extra = " and l.constituency = 'error'";
if ($rerun == 'missing_punc')
	$extra = " and l.missing_node regexp '^(" . mysql_real_escape_string(PUNCTUATION) . ")$'";
if ($rerun == 'failures')
	$extra = " and l.constituency != 'constituent'";
if ($rerun == 'all')
	$extra = "";

// Pick out the data from the database
$limit = $range ? "entry >= $start and entry <= $end and " : '';
$result = mysql_query("SELECT l.id, l.entry, l.text FROM " . LINKS_TABLE . " as l join " . ENTRIES_TABLE . " as e on l.entry = e.id where {$limit} length(e.stanford) > 0 and e.processed is not null and e.stanford not regexp '^SENTENCE_SKIPPED_OR_UNPARSABLE'{$extra} ORDER BY entry asc, id asc");

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

	// Strip out link delimiters if this is the first time seeing this entry
	if ($lastEntry != $entry) {
		$tree = str_replace("<<<LINK", "", $tree);
		$tree = str_replace("\nLINK;", "", $tree);
	}

	// Parse the tree with the text and determine the constituency
	set_time_limit(300);
	$results = doParse($tree, $text);
	$updatedColumns = stringifyResults($results, true);

	if(!$dryrun) {
		if (isset($results['error']) && $results['error'] == 'missing_parse') {
			echo "missing_parse: $entry{$break}";
			mysql_query("update " . ENTRIES_TABLE . " set stanford = '', processed = null, modified_by = '$user' where id = $entry limit 1");
			mysql_query("update " . LINKS_TABLE . " set processed = null, modified_by = '$user' where entry = $entry");
			$badEntryList[] = $entry;
			echo "Deleted parse as it was wrong!{$break}";
		} else {	
			// Update the database
			mysql_query("update " . LINKS_TABLE . " set $updatedColumns, processed = NOW(), modified_by = '$user' where entry = $entry AND id = $id");
		}
	}

	$newTree = addLINKs($tree, $text);
	
	if(!$dryrun) {
		if ($newTree) {
			// Update the database
			setTree($entry, $newTree);
			// echo "\t" . $tree . "\n\n";
			echo "updated tree with LINK.$break";
		} else {
			echo "didn't update tree with LINK$break";
		}
	} else {
		echo "tree with LINK: $newTree";
	}
	
	if (isset($updatedColumns))
		echo "result: $updatedColumns$break";
	
	echo "$split";
	
	$lastEntry = $entry;
}
