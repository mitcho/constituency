<?php

include_once('reports_functions.php');

searchAndRecordByCapturedWord('(VP {<NODE>} (NP NODE+ ) )', 'link on verb');
searchAndRecordByCapturedWord('(VP {NODE>} (NP NODE+ ) )', 'link through verb');
searchAndRecordByCapturedWord('<(VP {NODE} (NP NODE+ ) )>', 'link on VP');
searchAndRecordByCapturedWord('(VP {NODE} (NP NODE+ ) )>', 'link through VP');

// Note that 'link through verb' is a subset of 'link on verb', and the same with VP.
//
// We want to only look at links which end after a VP,
// but started higher.
// Of these links, we want to know what the proportion was that included
// just the verb, not the object.
//	$throughVerb = $counts['link through verb'] - $counts['link on verb'];
//	$throughVP = $counts['link through VP'] - $counts['link on VP'];

printResults();