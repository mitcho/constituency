<?php

include_once("../connect_mysql.php");

// return transitivity value, checking cache first.
// if no cached value, run _transitivity_lookup.
function transitivity_lookup($verb, $returnFirst = false) {
	// check cache first
	$results = mysql_query("select * from transitivity where verb = '$verb'");
	if ( $row = mysql_fetch_array($results) ) {
		extract($row);
	} else {
		$fresh = _transitivity_lookup($verb);
		if ( !$fresh )
			return false;
		extract($fresh);
	}
	
	if ( !$transitive && !$intransitive && !$ditransitive )
		return false;
	
	if ( $returnFirst )
		return $first;
	else
		return ($transitive + $ditransitive) / ($transitive + $intransitive + $ditransitive);
}

// this function actually does the wiktionary lookup
function _transitivity_lookup($verb) {
	$def = get_verb_def($verb);
	if ( !$def ) {
//		echo "no def\n";
		return false;
	}
		
	$transMatch = array();
	if ( !preg_match_all('/{{([^{|]+|)?((di|in)?transitive)(|[^}|]+)?}}/', $def, $transMatch) ) {
//		echo "no markers\n";
		return false;
	}
	// if we can't find any, then we don't know which is standard.
	
	$lines = array();
//	echo $def;
	$found_list = preg_match_all('/# (.*)/', $def, $lines);
	if ( !$found_list && (!isset($transMatch[2]) || !count($transMatch[2])) ) {
//		echo "no list and no original marker\n";
		return false;		
	}
	
	$markers = array_unique($transMatch[2]);

	if ( !$found_list || count($lines[0]) == count($transMatch[2]) || count($markers) > 1) {
		$counts = array_count_values($transMatch[2]);
		$first = $transMatch[1][0];
		
		extract($counts);
		$data = compact('transitive', 'intransitive', 'ditransitive', 'first');
		
		cache_valency($verb, $data);
		return $data;
	}
	
	// that unique marker:
	$unique_marker = $markers[0];
	$counts = array();
	$counts[$unique_marker] = count($transMatch[1]);
	$unmarkedCount = count($lines[0]) - count($transMatch[1]);

	$first_had_marker = preg_match('/{{([^{|]+|)?((di|in)?transitive)(|[^}|]+)?}}/', $lines[0][0]);

	switch ($unique_marker) {
		case 'transitive':
			$counts['intransitive'] = $unmarkedCount;
			if ($first_had_marker)
				$first = 'transitive';
			else
				$first = 'intransitive';				
			break;
		case 'intransitive':
			$counts['transitive'] = count($lines[0]) - count($transMatch[1]);
			if ($first_had_marker)
				$first = 'intransitive';
			else
				$first = 'transitive';
			break;
		case 'ditransitive':
			return false; // not sure what to do in this case.
	}

	extract($counts);
	$data = compact('transitive', 'intransitive', 'ditransitive', 'first');

	cache_valency($verb, $data);
	return $data;
}

function cache_valency($verb, $data) {
	extract($data);
	mysql_query("insert into transitivity (verb, transitive, intransitive, ditransitive, first) values ('$verb', '$transitive', '$intransitive', '$ditransitive', '$first')");
}

function get_verb_def($verb) {
	$wiki = curl_get_file_contents('http://en.wiktionary.org/w/api.php?action=parse&page=' . $verb . '&prop=wikitext');
	$matches = array();

	if ( !preg_match( '/===Verb===((\s|.)*?)==/m', $wiki, $matches ) )
		return false;
	return $matches[1];
}

function curl_get_file_contents($URL) {
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_URL, $URL);
	curl_setopt($c, CURLOPT_USERAGENT, 'Consituency project at MIT');
	$contents = curl_exec($c);
	curl_close($c);

	if ($contents) return $contents;
		else return FALSE;
}