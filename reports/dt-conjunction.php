<?php

include_once('reports_functions.php');

$results = array();
$results['linkWithFirstDet'] = array('secondDet' => 0, 'secondNoDet' => 0);
$results['linkWithoutFirstDet'] = array('secondDet' => 0, 'secondNoDet' => 0);

$linkWithInitialDet = searchAndReturnBackrefs('<(NP (NP (D WORD ) NODE+ ) (CC WORD ) (NP {NODE+} ) )>');
foreach($linkWithInitialDet as $instance) {
	foreach ($instance as $match) {
		$secondConjunct = $match[0];
		if (preg_match('/^\\((DT|CD) /', $secondConjunct))
			$results['linkWithFirstDet']['secondDet']++;
		else
			$results['linkWithFirstDet']['secondNoDet']++;
	}
}

$linkWithoutInitialDet = searchAndReturnBackrefs('(NP (NP (D WORD ) <NODE+ ) (CC WORD ) (NP {NODE+} )> )');
foreach($linkWithoutInitialDet as $instance) {
	foreach ($instance as $match) {
		$secondConjunct = $match[0];
		if (preg_match('/^\\((DT|CD) /', $secondConjunct))
			$results['linkWithoutFirstDet']['secondDet']++;
		else
			$results['linkWithoutFirstDet']['secondNoDet']++;
	}
}

var_dump($results);
