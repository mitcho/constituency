<?php

// Replace the <<<LINK...\nLINK; annotations in the db

// Provides common judgement functions
include("../functions.php");
// Connect to the database
include("../connect_mysql.php");

$web = isset($_SERVER['SERVER_PROTOCOL']) &&
       $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1';
$break = $web ? '<br/>' : "\n";
$split = $web ? '<hr/>' : "-----\n";

$start = false;
$end = false;
if (isset($_GET['start']))
	$start = (int) $_GET['start'];
if (isset($_SERVER['argv'][1]))
	$start = (int) $_SERVER['argv'][1];
if (isset($_GET['end']))
	$end = (int) $_GET['end'];
if (isset($_SERVER['argv'][2]))
	$end = (int) $_SERVER['argv'][2];

// Pick out the data from the database
$limit = $start && $end ? "entry >= $start and entry <= $end and " : '';
$links = $db->get_results("select l.id, l.entry, l.text, l.href, e.content from links as l join entries as e on l.entry = e.id where {$limit} l.sentence_length is null ORDER BY entry asc, id asc");

foreach ( $links as $link ) {
	// For each row in the retrieved data...
	$id = $link->id;
	$entry = $link->entry;
	
	$lengths = getLengths($link->content, $link->text, $link->href);
	
	echo "Link $link->entry:$link->id$break";
	echo "\t{$lengths['link']}, {$lengths['sentence']}$break";

	$db->update('links', array(
			'sentence_length' => (int) $lengths['sentence'],
			'link_length' => (int) $lengths['link']
		),
		array(
			'entry' => $link->entry,
			'id' => $link->id
		),
		'%d', '%d' // format, where_format
	);
	
	echo "$split";
}