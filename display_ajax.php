<?php
include("functions.php");
include("connect_mysql.php");

define('SUPPRESS_OUTPUT', true);

extract($_REQUEST);

if (!isset($action))
	die -1;

if (function_exists("action_$action")) {
	call_user_func( "action_$action" );
}

function action_display_parse() {
	global $db, $type, $id, $entry;
	
	header('Content-type: application/json');

	if ( !isset($id) || !isset($entry) )
		die -1;

	if ( !isset($type) )
		$type = 'original';

	$parse = $db->get_var(sprintf('select parse from parses where id = %d and type = "%s"', $entry, $type));
	if ( $parse === false || empty($parse) ) {
		echo json_encode( array('error' => 'unparsed') );
		exit;
	}
	
	$results = array('tree' => $parse);
	
	$text = $db->get_var(sprintf('select text from links where id = %d and entry = %d', $id, $entry));
	$treePattern = getPattern($text);
	$treePattern = '/\V*' . $treePattern . '\V*/'; // '/^.*' . $treePattern . '.*$/'

	if( preg_match($treePattern, $parse, $match) && isset($match[0]) ) {
		$tree = $match[0];
		$results['imageData'] = str_replace('"', '\\"', formatParseTree($tree));
	}
	
	// format tree with tabs
	$parse = trim(retabTree($parse, "  "));

	$link_parse = $db->get_row(sprintf('select * from link_parses where id = %d and entry = %d and type = "%s"', $id, $entry, $type), ARRAY_A);
	if ($link_parse)
		$results['link_parse'] = $link_parse;
	
	echo json_encode($results);
}