<?php

include_once('annotations_functions.php');

$idsToAnnotate = array();

$detAndRest = searchAndReturnBackrefs('(NP (NP NODE+ (POS WORD ) ) <{NODE+}> )');
foreach ($detAndRest as $entry => $instance) {
	foreach ($instance as $match) {
		// if the captured NODE+ is just one node, then it's already a constituent, so skip it...
		if (balanceAndCountZeroes($match[0]) <= 1)
			continue;
		$id = getEntryLinkID( $entry, $match[0] );
		if ($id)
			$idsToAnnotate[] = $id;
	}
}

echo 'dropped complex possessors: ' . count($idsToAnnotate) . "\n";

$detAndRest = searchAndReturnBackrefs('(NP (PRP$ WORD ) <{NODE+}> )');
foreach ($detAndRest as $entry => $instance) {
	foreach ($instance as $match) {
		// if the captured NODE+ is just one node, then it's already a constituent, so skip it...
		if (balanceAndCountZeroes($match[0]) <= 1)
			continue;
		$id = getEntryLinkID( $entry, $match[0] );
		if ($id)
			$idsToAnnotate[] = $id;
	}
}

echo 'dropped pronominal possessors: ' . count($idsToAnnotate) . "\n";

$detAndRest = searchAndReturnBackrefs('(NP (NP (NP NODE+ (POS WORD ) ) <{NODE+} ) {NODE+}> )');
foreach ($detAndRest as $entry => $instance) {
	foreach ($instance as $match) {
		$id = getEntryLinkID( $entry, $match[0] . ' ' . $match[1] );
		if ($id)
			$idsToAnnotate[] = $id;
	}
}

echo 'embedded dropped complex possessors: ' . count($idsToAnnotate) . "\n";

$detAndRest = searchAndReturnBackrefs('(NP (NP (PRP$ WORD ) <{NODE+} ) {NODE+}> )');
foreach ($detAndRest as $entry => $instance) {
	foreach ($instance as $match) {
		$id = getEntryLinkID( $entry, $match[0] . ' ' . $match[1] );
		if ($id)
			$idsToAnnotate[] = $id;
	}
}

echo 'embedded dropped pronominal possessors: ' . count($idsToAnnotate) . "\n";

annotate( $idsToAnnotate, 'Dropped possessor' );
