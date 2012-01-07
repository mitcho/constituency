<?php

include_once('annotations_functions.php');

$idsToAnnotate = array();

// BASIC TYPE WITH DETERMINER:
$detAndRest = searchAndReturnBackrefs('(NP (D WORD ) <{NODE+}> )');
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

echo 'basic type with det: ' . count($idsToAnnotate) . "\n";

// EMBEDDED TYPE, ONE LEVEL, WITH DETERMINER:
// Note that this seems to break? (NP (NP (D WORD ) <{NODE+ ) NODE+}> )
$detAndRest = searchAndReturnBackrefs('(NP (NP (D WORD ) <{NODE+} ) {NODE+}> )');
foreach ($detAndRest as $entry => $instance) {
	foreach ($instance as $match) {
		$id = getEntryLinkID( $entry, $match[0] . ' ' . $match[1] );
		if ($id)
			$idsToAnnotate[] = $id;
	}
}
echo 'embedded type with det: ' . count($idsToAnnotate) . "\n";

// BASIC TYPE WITH PREDET + DETERMINER:
$detAndRest = searchAndReturnBackrefs('(NP (PDT WORD ) (D WORD ) <{NODE+}> )');
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

echo 'basic type with predet+det: ' . count($idsToAnnotate) . "\n";

// EMBEDDED TYPE, ONE LEVEL, WITH PREDET + DETERMINER:
// Note that this seems to break? (NP (NP (D WORD ) <{NODE+ ) NODE+}> )
$detAndRest = searchAndReturnBackrefs('(NP (NP (PDT WORD ) (D WORD ) <{NODE+} ) {NODE+}> )');
foreach ($detAndRest as $entry => $instance) {
	foreach ($instance as $match) {
		$id = getEntryLinkID( $entry, $match[0] . ' ' . $match[1] );
		if ($id)
			$idsToAnnotate[] = $id;
	}
}
echo 'embedded type with predet+det: ' . count($idsToAnnotate) . "\n";

unset( $detAndRest );

// Two levels down, we seem to catch mostly things which drop determiners within their posessors
// This is actually an interesting query:
// (NP (NP (NP (D WORD ) <{NODE+} ) {NODE+} ) {NODE+}> )

// Three levels down, we get no catches on Search.

// BASIC TYPE WITH QUANTIFIERS
// many quantifiers are JJ, and these must be filtered manually. :(
$jjAndRest = searchAndReturnBackrefs('(NP (JJ {WORD} ) <{NODE+}> )');

foreach ($jjAndRest as $entry => $instance) {
	foreach ($instance as $match) {
		// skip jj's wich aren't known quantifiers...
		if ( !quantifierFilter($match[0]) )
			continue;
	
		// if the captured NODE+ is just one node, then it's already a constituent, so skip it...
		if (balanceAndCountZeroes($match[1]) <= 1)
			continue;
	
		$id = getEntryLinkID( $entry, $match[1] );
		if ($id)
			$idsToAnnotate[] = $id;
	}
}
echo 'basic type with q: ' . count($idsToAnnotate) . "\n";

// EMBEDDED TYPE, ONE LEVEL, WITH QUANTIFIERS:
$jjAndRest = searchAndReturnBackrefs('(NP (NP (JJ {WORD} ) <{NODE+} ) {NODE+}> )');

foreach ($jjAndRest as $entry => $instance) {
	foreach ($instance as $match) {
		// skip jj's wich aren't known quantifiers...
		if ( !quantifierFilter($match[0]) )
			continue;
	
		$id = getEntryLinkID( $entry, $match[1] . ' ' . $match[2] );
		if ($id)
			$idsToAnnotate[] = $id;
	}
}
echo 'embedded type with q: ' . count($idsToAnnotate) . "\n";

annotate( $idsToAnnotate, 'Dropped determiner' );

function quantifierFilter( $word ) {
	$knownQuantifiers = array(
		'many',
		'few',
		'some',
		'all',
		'that',
		'these',
		'the',
		'a',
		'an',
		'another',
		'this',
		'those',
		'their',
		'most',
		'both',
		'either',
		'no',
		'any',
		'every',
		'each',
		'neither',
		'such',
		'only'
	);

	if ( is_numeric($word) )
		return true;
	if ( array_search( $word, $knownQuantifiers ) !== false )
		return true;
//	echo "$word\n";
	return false;
}