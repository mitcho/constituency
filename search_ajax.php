<?php

include_once('search_functions.php');
include_once('functions.php');

if (isset($_REQUEST['tables'])) {
	define("ENTRIES_TABLE", "hyperlinks_entries_" . $_REQUEST['tables']);
	define("LINKS_TABLE", "hyperlinks_links_" . $_REQUEST['tables']);
	define("TAGS_TABLE", "tags_xref_" . $_REQUEST['tables']);
} else {
	define("ENTRIES_TABLE", "hyperlinks_entries");
	define("LINKS_TABLE", "hyperlinks_links");
	define("TAGS_TABLE", "tags_xref");
}

$tree = isset($_REQUEST['tree']);
$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : 0;
$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : 200;

if ( !isset($_REQUEST['query']) || empty($_REQUEST['query']) )
	json_encode(array('config' => compact(array('start', 'end', 'tree')),
	'results' => array()));

$query = isset($_REQUEST['query']) ? $_REQUEST['query'] : false;
$constituency = isset($_REQUEST['constituency']) ? $_REQUEST['constituency'] : false;

if (empty($constituency)) {
	$pattern = new Pattern($query);
	$results = $pattern->queryDb(array($start, $end));
	
	$regexp = "!(" . str_replace("\\\\", "\\", $pattern->regexp) . ")!";

	echo json_encode(array('config' => compact(array('start', 'end', 'query', 'tree')),
		'results' => array_map($tree ? 'withTreeHandler' : 'withoutTreeHandler', $results)));
	
} else {
	$sql = "select group_concat(l.id) as lids, e.id, e.stanford, e.content from " . LINKS_TABLE . " as l join " . ENTRIES_TABLE . " as e on (l.entry = e.id) left join " . TAGS_TABLE . " as t on (l.id = t.id and l.entry = t.entry) where constituency = '{$constituency}' and e.id >= {$start} and e.id <= " . ($end);

	// TODO: make this an option to opt-out:
	$forced_constituent = "(t.tid is not null and t.tid in (4,5,6,7))";
	$forced_error = "(t.tid is not null and t.tid in (1,3))";
	if ( true )
		$sql .= " and !{$forced_constituent} and !{$forced_error}";

	if ( isset($_REQUEST['failure']) && !empty($_REQUEST['failure']) )
		$sql .= " and failure_type = '{$_REQUEST['failure']}'";
	if ( isset($_REQUEST['immediate']) && !empty($_REQUEST['immediate']) )
		$sql .= " and immediate_node = '{$_REQUEST['immediate']}'";
	if ( isset($_REQUEST['missing']) && !empty($_REQUEST['missing']) )
		$sql .= " and missing_node = '{$_REQUEST['missing']}'";

	$sql .= " group by e.id";

	$mysql_results = mysql_query($sql);
	$results = array();
	while ($row = mysql_fetch_assoc($mysql_results)) {
		$results[] = $row;
	}
	
	echo json_encode(array('config' => $_GET,
		'results' => array_map($tree ? 'withTreeHandler' : 'withoutTreeHandler', $results)));
}

function withTreeHandler($result) {
	global $regexp, $formatMatches;

	extract($result);
	$trees = split("\n\n", $stanford);
	$treedata = array();
	foreach ($trees as $tree) {
		$tree = formatParseTree($tree, $regexp, true);
		if ($tree) {
			$treedata[] = stripslashes($tree);

			if (!empty($formatMatches) && !isset($lids)) {
				foreach ($formatMatches as $match) {
					$match = str_replace('<<<LINK', '', $match);
					$match = str_replace('LINK;', '', $match);
	
					$match = preg_replace('/\\(\\S+/', '', $match);
					$match = str_replace(')', '', $match);
	
					// This looks stupid, but just turn stretches of space into allowing potentially
					// no space... this is because punctuation could have had spaces added between
					// them as they're different nodes.
					$match = '!' . preg_replace('/[[:space:]]+/', '([[:space:]]|<\\/a>|<a href=[\'"][^"\']*[\'"]>)*', trim(preg_quote($match))) . '!';
	
					$content = preg_replace_callback($match, 'matchSpanHandler', $content );
				}
				// find matches to properly mark the content:
				$matches = array();
				preg_match_all('/\\*\\*{(.*?)}\\*\\*/m', $tree, $matches);
			}
		}
	}

	if ( isset($lids) ) {
		$lids = split(',', $lids);
		foreach ($lids as $lid) {
			$regex = '!((?:.|\\s)*?' . str_repeat('<a href=[\'"][^"\']*[\'"](?: class="match")?>(?:.|\\s)*?', $lid) . ')<a href=[\'"]([^"\']*)[\'"]>!m';

			$oldcontent = $content;
			if (!($content = preg_replace($regex, '\1<a href="\2" class="match">', $content)))
				$content = $oldcontent;
		}
	}

	$fields = array('id', 'content', 'treedata');
	if ( isset($lid) )
		$fields[] = 'lid';
	return compact($fields);
}

function matchSpanHandler( $matches ) {
	$text = $matches[0];

	$a = '<span class="match">';
	$z = '</span>';
	
	$text = str_replace('</a>', "$z</a>$a", $text );
	$text = preg_replace('!<a href="[^"]*">!', "$z\\0$a", $text );
	
	return $a . $text . $z;
}

function withoutTreeHandler($result) {
	extract($result);
	return compact(array('id', 'content'));
}