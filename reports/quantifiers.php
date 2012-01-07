<?php

ini_set('memory_limit', '1G');

include_once('reports_functions.php');

searchAndRecordByCapturedWord('(NP (DT {WORD} ) <NODE+> )', 'link without DT');
searchAndRecordByCapturedWord('<(NP (DT {WORD} ) NODE+ )>', 'link with DT');
// The way things are glossed in PTB, some quantifiers are JJ.
// Unfortunately, these capture *all adjectives*! Gross. Filter later.
searchAndRecordByCapturedWord('(NP (JJ {WORD} ) <NODE+> )', 'link without JJ');
searchAndRecordByCapturedWord('<(NP (JJ {WORD} ) NODE+ )>', 'link with JJ');

$acceptedKeys = array_filter( array_keys( $allWords ), 'quantifierFilter' );

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

$allWords = array_intersect_key( $allWords, array_flip($acceptedKeys) );

// merge experiments 1, 2 with 3, 4, respectively
global $labels;
$labels = array('link without', 'link with');
foreach ($allWords as $word => $counts) {
	$allWords[$word] = array('link without' => $counts['link without DT'] + $counts['link without JJ'],
													'link with' => $counts['link with DT'] + $counts['link with JJ']);
}

echo "word\tlink without\tlink with\n";
printResults();
