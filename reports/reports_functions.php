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

$allWords = array();
$labels = array();

function searchAndRecordByCapturedWord( $query, $label, $includeNodeLabel = false, $allowStrayLINKs = false ) {
	global $labels, $debug, $range;
	
	$labels[] = $label;
	$pattern = new Pattern($query);
	
	if ($debug)
		$range = array(0, 1000);

	$results = $pattern->queryDb($range, $allowStrayLINKs);
//var_dump($pattern->backrefs);
	foreach ($pattern->backrefs as $instance) {
		foreach ($instance as $match) {
			$word = strtolower($match[0]);
			$matches = array();
			if ($includeNodeLabel) {
				if ( !preg_match('/\((\w+) (\w+)\)/', trim($word), $matches) )
					// if we don't match this, it's not just a single word...
					continue;
				// todo: add stemming of verbs
				$word = $matches[1] . "\t" . strtolower($matches[2]);
			}
	
			record($word, $label);
		}
	}
}

function searchAndReturnBackrefs( $query, $allowStrayLINKs = false ) {
	global $debug, $range;
	
	$pattern = new Pattern($query, $allowStrayLINKs);
	
	if ($debug)
		$range = array(0, 1000);

	$results = $pattern->queryDb($range);
	return $pattern->backrefs;
}

function searchAndCount( $query, $allowStrayLINKs = false ) {
	global $debug, $range;
	
	$pattern = new Pattern($query, $allowStrayLINKs);
	
	if ($debug)
		$range = array(0, 1000);

	return count($pattern->queryDb($range));
}

function record($word, $label) {
	global $allWords;
	if ( !isset( $allWords[$word] ) )
		$allWords[$word] = array();
	if ( !isset( $allWords[$word][$label] ) )
		$allWords[$word][$label] = 0;
	$allWords[$word][$label]++;	
}

function printHeader() {
	global $labels;
	echo implode("\t", $labels) . "\n";
}

function printResults() {
	global $labels, $allWords;
	
	foreach ( $allWords as $word => $counts ) {
		echo $word;
		foreach ($labels as $label) {
			echo "\t" . ( isset($counts[$label]) ? $counts[$label] : 0 );
		}
		echo "\n";
	}
}

function printResultsWithTransitivity() {
	include_once('transitivity_functions.php');

	global $labels, $allWords;
	
	$labels[] = 'transitivity';
	
	foreach ( array_keys($allWords) as $word ) {
		$words = split("\t", $word);
		$allWords[$word]['transitivity'] = transitivity_lookup($words[1]);
	}
	
	printResults();
}