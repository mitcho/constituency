<?php

include_once('reports_functions.php');

$postVerbal = searchAndReturnBackrefs('(VP <(V WORD ) {NODE+} )', true); // true = allow stray LINKs

// REPORT IN PROGRESS:
$numberOfPostVerbalNodes = array();
$results = array();
foreach ($postVerbal as $id => $entry) {
	foreach ($entry as $instance) {
		$postVerbalNodes = $instance[0];
	
		// We only care if the post-verbal material includes a closing of the link.
		if (strpos($postVerbalNodes, 'LINK;') === false)
			continue;
	
		$numberOfPostVerbalNodes[] = balanceAndCountZeroes($postVerbalNodes);
	
		$linkParts = split('LINK;', $postVerbalNodes);
		$postVerbalLink = $linkParts[0];
	
		// now, crucially, we want to know if this post-verbal link part is a constituent or not.
		$results[] = (balanceAndCountZeroes($postVerbalLink) != 0);
	}
}

echo "number of post-verbal nodes:\n";
$tabulatedNumberOfPostVerbalNodes = array_count_values($numberOfPostVerbalNodes);
//asort($tabulatedNumberOfPostVerbalNodes);
foreach ($tabulatedNumberOfPostVerbalNodes as $value => $count) {
	echo "$value\t$count\n";
}

echo "results:\n";
$tabulatedResults = array_count_values($results);
//asort($tabulatedResults);
foreach ($tabulatedResults as $value => $count) {
	echo "$value\t$count\n";
}
