<?php

// Print report of counts by sentence_length x link_length

// Provides common judgement functions
include("../functions.php");
// Connect to the database
include("../connect_mysql.php");

$web = isset($_SERVER['SERVER_PROTOCOL']) &&
       $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1';
$break = $web ? '<br/>' : "\n";
$split = $web ? '<hr/>' : "-----\n";

$tag = false;
if (isset($_GET['tag']))
	$tag = (int) $_GET['tag'];
if (isset($_SERVER['argv'][1]))
	$tag = (int) $_SERVER['argv'][1];

// Pick out the data from the database
$join = $tag ? " join tags_xref on (links.id = tags_xref.lid and links.entry = tags_xref.entry and tags_xref.tid = $tag)" : '';
$dist = $db->get_results("select sentence_length, link_length, count(constituency) as links, sum(constituency = 'constituent') as constituents
from links {$join}
left join
(select * from (select id, entry, constituency from link_constituency order by date desc) as t
group by id, entry) as c
on (links.id = c.id and links.entry = c.entry)
group by sentence_length, link_length
having links > 0");

echo "sentence_length\tlink_length\tactual\texpected\n";

bcscale(40);
// $mindiff = 1;
$i = 0;
$fudge_factor = 0.01;
foreach ( $dist as $cell ) {
	// computed expected number
	// P(n-word constituent in m-word sentence) = (C(m - n) * C(n - 1) / C(m - 1))
	$p = bcdiv( bcmul(C($cell->sentence_length - $cell->link_length), C($cell->link_length - 1)), C($cell->sentence_length - 1) );
	$expected = bcmul($p, $cell->links);
	
	$line = array((int) $cell->sentence_length, (int) $cell->link_length, (int) $cell->constituents, $expected);
	
	// if actual = expected, add the fudge factor to one side or the other.
	if ( (float) $cell->constituents === (float) $expected )
		if ( ($i++)%2 )
			$line[2] += $fudge_factor / $i;
		else
			$line[3] += $fudge_factor / $i;		
	
	// Calculation of mindiff, the minimum meaningful difference between the actual and expected.
	// This came out to be 0.0356 with Sample 1000
//	if ( (float) $cell->constituents !== (float) $expected )
//		$mindiff = min($mindiff, abs((float) $cell->constituents - (float) $expected) );
	echo join("\t", $line) . "\n";
}

function C($x = 0) {
	return bcdiv(fact($x*2), bcmul(fact($x), fact($x+1)));
	// this is based on the following definition: (2n)!/(n!(n+1)!)
}

function fact($int){
	if ($int<2) return 1;
	for ($f=2; $int-1>1; $f = bcmul($f, ($int--)));
	return $f;
}
