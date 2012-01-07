<?php

$time_start = microtime_float();

error_reporting( E_ALL );

include_once("connect_mysql.php");
include_once("functions.php");

if(!isset($argv))
	$argv = array();
$args = parseArgs($argv);
extract($args);

?><html>
<head>
<title>The &lt;a&gt;constituent&lt;/a&gt; Project | Results dashboard</title>
<style>
.dist {
	border: 1px solid black;
	display: inline;
	padding: 0px;
}

.dist a {
	text-decoration: none;
}
</style>
</head>
<body>
<h1>The &lt;a&gt;constituent&lt;/a&gt; Project</h1>

<p>All stats are now post-tagging. Links with the tags "dropped determiner," "dropped possessor," "NP branching," and "proper noun." Links and entries with the tag "bad parse" and "bad split" are not counted in stats at all.</p>

<?php

// Post-tagging setup:
$joined_tables = LINKS_TABLE . " as l left join " . TAGS_TABLE . " as t on ((l.id = t.lid || 0 = t.lid) and l.entry = t.entry)";
$forced_constituent = "(t.tid is not null and t.tid in (4,5,6,7))";
$forced_error = "(t.tid is not null and t.tid in (1,3))";
$distinct_count = "count(distinct concat(l.id, '-',l.entry))";

?>

<h2>Overall corpus stats:</h2>
<p><small>Percentages are out of all links which did not result in errors.</small></p>

<?php

$results = mysql_query("select {$distinct_count} as count from {$joined_tables} where (constituency != 'error' and processed is not null and !{$forced_error}) or {$forced_constituent}");
$row = mysql_fetch_array($results);
$total = $row['count'];

// tid 5 is the tag for dropped DT's.
// tid 6 is for proper names
// tid 7 is the tag for dropped possessors (similar to dropped DT's)
$results = mysql_query("select if({$forced_constituent},'constituent',if(missing_node = 'ERROR','error',constituency)) as code, {$distinct_count} as count, if({$forced_constituent},NULL,if(missing_node = 'ERROR',NULL,failure_type)) as failure from {$joined_tables} where processed is not null and !{$forced_error} and (error != 'missing_parse' or error is null) group by code, failure");

echo "<table><thead><th></th><th>N</th><th>Proportion</th></thead><tbody>";
while ($row = mysql_fetch_array( $results )) {
	if ($row['code'] == 'error')
		$p = 'N/A';
	else
		$p = round($row['count'] / $total * 100, 2) . '%';
	if (strlen($row['failure']))
		$row['code'] .= ': ' . $row['failure'];
	echo "<tr><th>{$row['code']}</th><td>{$row['count']}</td><td>$p</td></tr>";
}
echo "</tbody></table>";

// TODO: retire this stat?
$results = mysql_query("select count(*) as count from " . LINKS_TABLE . " where (constituency = 'multiple_constituents' or constituency = 'not_constituent') and immediate_node = 'NP' and missing_node = 'DT' and failure_type = 'missing_before'");
$row = mysql_fetch_array($results);
$DP = $row['count'];
$p = round($row['count'] / $total * 100, 2) . '%';
echo "<p>Number of DP/D = {$DP} ({$p})</p>";

echo "<h2>Status</h2>";

$results = mysql_query("select sum(words) as words, count(id) as count from " . ENTRIES_TABLE);
$row = mysql_fetch_array($results);
$words = $row['words'];
$count = $row['count'];
$avg_words = round($words / $count, 2);
$results = mysql_query("select count(id) as links from " . LINKS_TABLE);
$row = mysql_fetch_array($results);
$links = $row['links'];
$avg_links = round($links / $count, 2);
echo "<p>Corpus size: {$words} words ({$avg_words} words / entry), {$links} links ({$avg_links} links / entry)</p>";

echo "<table>
<tr><th></th><th>Count</th><th>Max</th><th>Coverage</th><th></th><th></th></tr>";

$results = mysql_query("select count(id) as total, sum(if(annotation is null,1,0)) as count, max(id) as max from " . ENTRIES_TABLE);
$row = mysql_fetch_array($results);
$realtotal = $row['total'];
$total = $row['count'];
$max = $row['max'];
echo "<tr><th>Scraped entries:</th><td>{$row['count']}</td><td>{$row['max']}</td></td><td><td></td><td></td></tr>";

$results = mysql_query("select count(id) as count, max(id) as max from " . ENTRIES_TABLE . " where processed is not null and length(stanford) != 0");
$row = mysql_fetch_array($results);
$p = round($row['count'] / $total * 100, 2) . '%';
$parsed_entries = $row['max'];
$start = $row['max'] + 1;
echo "<tr><th>Processed entries:</th><td>{$row['count']}</td><td>{$row['max']}</td><td>$p</td><td class='dist'>";
$results = mysql_query("select min(id) as minid, max(id) as maxid, sum(if(processed is null, 0, 1)) / sum(if(annotation is null, 1, 0)) as proc from " . ENTRIES_TABLE . " group by floor(id/500)");
while ($row = mysql_fetch_array($results)) {
	$percent = round($row['proc'] * 100, 2). '%';
	echo "<a style='background-color: rgba(0,0,0,{$row['proc']})' href='parse_entries.php?start={$row['minid']}&end={$row['maxid']}' title='{$row['minid']}-{$row['maxid']}: {$percent}'>&nbsp;</a>";
}
echo "</td><td><a href='parse_entries.php'>Parse more</a>, <a href='parse_entries.php?rerun=failures'>reparse errors</a></td></tr>";

$results = mysql_query("select count(distinct entry) as count, max(entry) as max from " . LINKS_TABLE . " where processed is not null and (error is null or error != 'missing_parse')");
$row = mysql_fetch_array($results);
$p = round($row['count'] / $total * 100, 2) . '%';
$start = $row['max'] + 1;
echo "<tr><th>Links judged:</th><td>{$row['count']}</td><td>{$row['max']}</td><td>$p</td><td class='dist'>";
$results = mysql_query("select min(entry) as minid, max(entry) as maxid, sum(if(processed is null, 0, 1)) / count(id) as proc from " . LINKS_TABLE . " group by floor(entry/500)");
while ($row = mysql_fetch_array($results)) {
	$percent = round($row['proc'] * 100, 2). '%';
	echo "<a style='background-color: rgba(0,0,0,{$row['proc']})' href='parse_entries.php?start={$row['minid']}&end={$row['maxid']}' title='{$row['minid']}-{$row['maxid']}: {$percent}'>&nbsp;</a>";
}
echo "</td><td><a href='judge_constituency.php'>Parse more</a>, <a href='judge_constituency.php?rerun=errors'>reparse errors</a></td></tr>";
echo "</table>";

echo "<p>Coverage doesn't include those entries that we ran out of memory on: " . ($realtotal - $total) . " (" . round((1 - $total/$realtotal) * 100, 2) . "%)</p>";



echo "<h2>Missing nodes (multi-constituents)</h2>";

$results = mysql_query("select {$distinct_count} as count from {$joined_tables} where (missing_node is not null and missing_node != 'ERROR' and constituency = 'multiple_constituents') and !{$forced_constituent} and !{$forced_error}");
$row = mysql_fetch_array($results);
$total = $row['count'];
$results = mysql_query("select missing_node, failure_type, group_concat(distinct immediate_node order by count desc) as nodelist, sum(count) as count from
(select missing_node, failure_type, immediate_node, {$distinct_count} as count from {$joined_tables} where missing_node is not null and missing_node != 'ERROR' and constituency = 'multiple_constituents' and !{$forced_constituent} group by missing_node, failure_type, immediate_node) as sub
group by missing_node, failure_type order by count desc");

$tables_part = ($tables != 'original') ? "tables=$tables" : '';
echo "<table><thead><th>missing node</th><th>before/after</th><th>immediate dominating node</th><th>N</th><th>Proportion</th></thead><tbody>";

global $terminalNodeLabels;
while ($row = mysql_fetch_array( $results )) {
	$p = round($row['count'] / $total * 100, 2) . '%';
	$class = (($row['count'] / $total) < 0.01) ? " class='longtail'" : '';
	$nodelist = array();
	foreach (explode(',', $row['nodelist']) as $node) {
		$nodelist[] = "<a href='search.php?{$tables_part}&constituency=multiple_constituents&failure={$row['failure_type']}&missing_node={$row['missing_node']}&immediate_node={$node}'>{$node}</a>";
	}
	$row['failure_type'] = str_replace('missing_','',$row['failure_type']);
	echo "<tr{$class}><th>{$row['missing_node']}</th><td>{$row['failure_type']}</td><td>" . implode(', ', $nodelist) . "</td><td>{$row['count']}</td><td>$p</td></tr>";
}
echo "</tbody></table>";



echo "<h2>Missing nodes (misc. non-constituents)</h2>";
$results = mysql_query("select {$distinct_count} as count from {$joined_tables} where missing_node is not null and missing_node != 'ERROR' and constituency = 'not_constituent' and !{$forced_constituent} and !{$forced_error}");
$row = mysql_fetch_array($results);
$total = $row['count'];
$results = mysql_query("select missing_node, failure_type, group_concat(distinct immediate_node order by count desc) as nodelist, sum(count) as count from
(select missing_node, failure_type, immediate_node, {$distinct_count} as count from {$joined_tables} where missing_node is not null and constituency = 'not_constituent' and missing_node != 'ERROR' and !{$forced_constituent} and !{$forced_error} group by missing_node, failure_type, immediate_node) as sub
group by missing_node, failure_type order by count desc");

echo "<table><thead><th>missing node</th><th>before/after</th><th>immediate dominating node</th><th>N</th><th>Proportion</th></thead><tbody>";
while ($row = mysql_fetch_array( $results )) {
	$p = round($row['count'] / $total * 100, 2) . '%';
	$class = (($row['count'] / $total) < 0.01) ? " class='longtail'" : '';

	$nodelist = array();
	foreach (explode(',', $row['nodelist']) as $node) {
		$nodelist[] = "<a href='search.php?{$tables_part}&constituency=not_constituent&failure={$row['failure_type']}&missing_node={$row['missing_node']}&immediate_node={$node}'>{$node}</a>";
	}

	$row['failure_type'] = str_replace('missing_','',$row['failure_type']);
	echo "<tr{$class}><th>{$row['missing_node']}</th><td>{$row['failure_type']}</td><td>" . implode(', ', $nodelist) . "</td><td>{$row['count']}</td><td>$p</td></tr>";
}
echo "</tbody></table>";

$time_end = microtime_float();
$time = round($time_end - $time_start,2);

echo "<p>$time seconds</p>";

?>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-19567124-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script></body></html><?php

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
