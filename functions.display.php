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
	if (!$random)
		$random = 'true'; // just for randomLink purposes
	$args = array('random' => $random);
	if ( isset($_GET['debug']) )
		$args['debug'] = 1;
	if ( isset($_GET['tables']) )
		$args['tables'] = $_GET['tables'];
	
	return 'display.php?' . http_build_query($args);
}

// $dir == 'next' or 'prev'
function getNextPrevLink( $entry, $id, $dir ) {
	global $db;
	
	$compare = $dir == 'next' ? '>' : '<';
	$order = $compare == '>' ? 'asc' : 'desc';
	$result = $db->get_row("select entry, id from " . LINKS_TABLE . " where entry $compare $entry or (entry = $entry and id $compare $id) order by entry $order, id $order limit 1");

	if ( $result === false || empty($result) )
		return false;
	
	return permalink($result->entry, $result->id);
}