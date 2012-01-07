<?php

// Runs the Stanford parser on the database entries.

$web = isset($_SERVER['SERVER_PROTOCOL']) &&
       $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1';
$break = $web ? '<br/>' : "\n";
$split = $web ? '<hr/>' : "-----\n";
include("connect_mysql.php");
include("functions.php");

$args = parseArgs($argv);
extract($args);
$start = $range ? $range[0] : false;
$end = $range ? $range[1] : false;

// add the rerun filters
$extra = " and (processed is null or length(stanford) = 0)";
if ($rerun == 'failures')
	$extra = " and stanford like '%SENTENCE_SKIPPED_OR_UNPARSABLE%' and annotation is null";
if (preg_match('/^\d+$/', $rerun))
	$extra = " and (processed is null or length(stanford) = 0) and words < {$rerun} and annotation is null";
if ($rerun == 'memory_failures')
	$extra = " and annotation = 'out_of_memory'";
if ($rerun == 'all')
	$extra = "";

// Pick out the data from the database
$limit = $range ? "id >= $start and id <= $end and " : '';
$queryResult = mysql_query("SELECT id, content FROM " . ENTRIES_TABLE . " WHERE {$limit}length(content) > 0{$extra} order by id asc");

$tryAgain = false;
while ($tryAgain || $entry = mysql_fetch_array($queryResult)) {
	
	$id = $entry["id"];
	$text = $entry["content"];
	
	$text = prepHTMLForParser($text);
	$text = trim(splitSentences($text));

	if (!$text) {
		echo "Failed to split sentences.\n" . $split;
		if(!$dryrun)
			mysql_query("UPDATE " . ENTRIES_TABLE . " SET annotation = 'split_failed', processed = NOW() WHERE id = $id LIMIT 1");
		continue;
	}

	$stanfordTree = parseStanford($text, 'penn');

	$count = str_word_count($text);
	echo "{$id} has {$count} words {$break}";

	if(!$dryrun) {
		// Prepare for MySQL query
		$stanfordTree = trim(mysql_real_escape_string($stanfordTree));
		
		$affected = 0;
		if ($stanfordTree && strlen(trim($stanfordTree)) && strpos($stanfordTree, "SENTENCE_SKIPPED_OR_UNPARSABLE") === false) {
			mysql_query("UPDATE " . ENTRIES_TABLE . " SET stanford = '$stanfordTree', words = {$count}, processed = NOW(), annotation = null WHERE id = $id LIMIT 1");
			$affected = mysql_affected_rows();
			echo "recorded tree in {$affected} entry.{$break}";
			if ($affected <= 0) {
				echo "mysql error " . mysql_errno() . ": " . mysql_error() . $break;
				if (mysql_errno() == 2006) {// we lost database access!
					if (mysql_ping())
						echo "Connection is up... hmm... {$break}";
					else
						echo "Connection is down!{$break}";
					echo "Trying to reset MySQL...{$break}";
					mysql_close();
					global $session;
					include("connect_mysql.php");
					if (!mysql_ping())
						die("Could not reconnect!");
					echo $split;
					continue;
				}
			}
		}
		
		if ($affected <= 0 && !mysql_errno()) {
			// It failed, memory issues, mark this entry as erroneous
			echo "NO PARSE or couldn't save! Resetting parser.{$break}";
			closeParser('penn');
			if (!$tryAgain) {
				echo "Let's try again!{$break}";
				$tryAgain = true;
			} else {
				echo "This was a second try. Don't try again.{$break}";
				mysql_query("UPDATE " . ENTRIES_TABLE . " SET annotation = 'out_of_memory', processed = NOW() WHERE id = $id LIMIT 1");
				$tryAgain = false;
			}
		} else {
			// no need to try again.
			$tryAgain = false;
		}
	}
	echo $split;
}

