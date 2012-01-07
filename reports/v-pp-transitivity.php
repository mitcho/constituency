<?php

include_once('reports_functions.php');

searchAndRecordByCapturedWord('(VP <(V {WORD} )> (PP NODE+ ) )', 'link on verb');
//searchAndRecordByCapturedWord('(VP {NODE} (NP NODE+ ) )', 'no link specified', true);
//searchAndRecordByCapturedWord('(VP {NODE} <(NP NODE+ )> )', 'link on object', true);
searchAndRecordByCapturedWord('<(VP (V {WORD} ) (PP NODE+ ) )>', 'link on VP');

$labels[] = 'pp-transitivity';
$labels[] = 'np-transitivity';
$labels[] = 'intransitivity';

foreach($allWords as $verb => $results) {
	$allWords[$verb]['pp-transitivity'] = searchAndCount( "(VP (V $verb ) (PP NODE+ ) )" );
	$allWords[$verb]['np-transitivity'] = searchAndCount( "(VP (V $verb ) (NP NODE+ ) )" );
	$allWords[$verb]['intransitivity'] = searchAndCount( "(VP (V $verb ) )" );
}

printHeader();
printResults();
