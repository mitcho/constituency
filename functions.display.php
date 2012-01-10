<?php

function formatDisplayEntry($content, $text, $href) {
	$prePattern = preg_quote($href) . '\V+' . preg_quote($text);
	$prePattern = preg_replace("!(/)!", '\/', $prePattern);
	$pattern = '/\V*' . $prePattern . '\V*/';
	preg_match($pattern, $content, $match);
	if(isset($match[0]))
		$text = $match[0];

	// make the desired link colored.
	$aPattern = '<a href=["\']' . preg_quote($href) . '["\']';
	$aPattern = preg_replace("!(/)!", '\/', $aPattern);
	$text = preg_replace("/$aPattern/", '$0 class="desired-link"', $text);

	// process text slightly to remove unsightly things
	$text = trim($text);
	$text = preg_replace('/^<br>/', '', $text);
	
	return $text;
}

function permalink($entry, $id) {
	$args = array(
		'entry' => $entry,
		'id' => $id
	);
	if ( isset($_GET['debug']) )
		$args['debug'] = true;
	if ( isset($_GET['tables']) )
		$args['tables'] = $_GET['tables'];
	
	return 'display.php?' . http_build_query($args);
}

function randomLink($random) {
	$args = array('random' => true);
	if ( isset($_GET['debug']) )
		$args['debug'] = 1;
	if ( isset($_GET['tables']) )
		$args['tables'] = $_GET['tables'];
	
	return 'display.php?' . http_build_query($args);
}