<?php

/**
 * UNIT TESTS
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('America/New_York');

$success = 0;
$fail = 0;

$web = isset($_SERVER['SERVER_PROTOCOL']) &&
       $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1';
$break = $web ? '<br/>' : "\n";
$split = $web ? '<hr/>' : "-----\n";

require_once 'functions.php';
require_once 'search_functions.php';

if ($web)
	echo "<code>";

foreach( glob('tests/*.php') as $testfile ) {
	if (isset($argv[1]) && basename($testfile) != $argv[1])
		continue;
	if (isset($_GET['test']) && basename($testfile) != $_GET['test'])
		continue;

	$getPatternCache = array();
	require $testfile;
}

function test_assert( $assertion, $message = '' ) {
	global $test_message, $fail, $success, $split, $break;
	$test_message = $message;
	$stack = debug_backtrace();
	$debug = "(". basename($stack[0]['file']) .", line {$stack[0]['line']})";
	if ( $assertion ) {
		$success ++;
		echo "TEST PASSED! {$debug}{$split}";
	} else {
		$fail ++;
		echo "TEST FAILED! {$debug}";
		if ( strlen($message) )
			echo "{$break} {$message}";
		echo $split;
	}
}

$total = $success + $fail;
if ( $fail === 0 )
	echo "{$break}ALL TESTS PASSED! ({$success}/{$total}){$split}";
else
	echo "{$break}{$fail}/{$total} TESTS FAILED! :({$split}";

if ($web)
	echo "</code>";