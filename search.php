<html>
<head>
<title>The &lt;a&gt;constituent&lt;/a&gt; Project | Search</title>
<style type="text/css">
.dist {
	border: 1px solid black;
	display: inline;
	padding: 0px;
}

.dist a {
	text-decoration: none;
}

html {
	-moz-tab-size: 2;
}

a {
	color: #000;
}

.match {
	color: red;
	font-weight: bold;
}

.content a {
	color: #00e; /* default blue */
}

.searchbox {
	float: left;
	border: 1px solid #ccc;
	border-radius: 5px;
	margin-left: 10px;
	padding: 10px;
	color: #999;
}

.searchbox h4 {
	margin: 0px;
}

#searchboxes {
	overflow: auto;
}

.searchbox.active {
	color: #000;
	border-color: #999;
}

</style>
<script src="http://code.jquery.com/jquery-1.6.1.min.js"></script>
<script>
jQuery(function($){
	$('.searchbox').mouseover(function() {
		$(this).addClass('active');
	});
	$('.searchbox').mouseout(function() {
		if (!$(this).is(':focus'))
			$(this).removeClass('active');
	});

	$('.searchbox input').bind('focus change', function() {
		$(this).closest('.searchbox').addClass('active');
		$(this).closest('.searchbox').siblings().removeClass('active');
	});
	$('.searchbox input').blur(function() {
		$(this).closest('.searchbox').removeClass('active');
	});
});
</script>
</head>
<body>

<?php

$time_start = microtime_float();

error_reporting( E_ALL );
ini_set('display_errors', true);

include_once('functions.php');
include_once('search_functions.php');
if (isset($_REQUEST['tables']) && !empty($_REQUEST['tables'])) {
	$tables = $_REQUEST['tables'];
	define("ENTRIES_TABLE", "hyperlinks_entries_" . $_REQUEST['tables']);
	define("LINKS_TABLE", "hyperlinks_links_" . $_REQUEST['tables']);
} else {
	$tables = '';
	define("ENTRIES_TABLE", "hyperlinks_entries");
	define("LINKS_TABLE", "hyperlinks_links");
}

$full = isset($_REQUEST['full']) ? $_REQUEST['full'] : 'ajax';

$constituency = isset($_REQUEST['constituency']) ? $_REQUEST['constituency'] : false;
$failure = isset($_REQUEST['failure']) ? $_REQUEST['failure'] : false;
$missing_node = isset($_REQUEST['missing_node']) ? $_REQUEST['missing_node'] : false;
$immediate_node = isset($_REQUEST['immediate_node']) ? $_REQUEST['immediate_node'] : false;

?>
<h3>Query syntax:</h3>
<ol>
<li>parentheses in queries are understood to match.</li>
<li><code>NODE</code> is a special wildcard for a syntactic node, and <code>WORD</code> is a special wildcard for a terminal. We understand + and *.</li>
<li>Node labels may be unspecified, e.g. <code>(NP ( few ) NODE+ )</code>.</li>
<li>Any space can be understood as whitespace, including line breaks.</li>
<li>&lt; and &gt; are understood to mean beginning and end of a link.</li>
<li>Enclosing an element in {} will 'capture' it and produce a table showing how often each word/phrase appears in the captured area.</li>
<li>TODO: think about domination relations?</li>
</ol>

<h3>Node label abbreviations</h3>
<p>The search recognizes node labels according to <a href='http://repository.upenn.edu/cgi/viewcontent.cgi?article=1603&context=cis_reports'>the Penn Treebank (PTB) standard</a>. However, to make my life easier, the following aliases have been implemented:</p>
<ul>
<li>N = NN, NNS, NNP, NNPS</li>
<li>A = JJ, JJR, JJS</li>
<li>D = DT, CD, PDT, but note that many quantifiers are unfortunately called JJ (adj) by PTB</li>
<li>P = IN</li>
<li>V = MD, VB, VBD, VBG, VBN, VBP, VBZ</li>
<li>ADV = RB, RBR, RBS</li>
</ul>
<div id="searchboxes">

<form method="get" id="syntaxsearch" class="searchbox <?php if (empty($constituency)) echo ' active';?>">
<h4>Search by syntactic query</h4>
<input type="hidden" name="tables" value="<?php echo $tables ?>"/>
<p>
<input type="search" name="query" id="query" placeholder="(NP (NP (DT the ) <(JJS WORD ) NODE+ )> NODE+ )" size="40"<?php
if (isset($_REQUEST['query']))
	echo " value='" . htmlspecialchars($_REQUEST['query']) . "'";
?>>
</p>

<p>
Full (ajax) search?
<input type="radio" name="full" value="ajax"<?php
if ($full == 'ajax')
	echo " checked";
?>>
Just (static) stats?
<input type="radio" name="full" value="stats"<?php
if ($full == 'stats')
	echo " checked";
?>>
</p>

<p>
Output tree?
<input type="checkbox" name="tree"<?php
if (isset($_REQUEST['tree']))
        echo " checked";
?>>
</p>

<input type="submit" value="Search!"/> 
</form>

<form method="get" id="typesearch" class="searchbox <?php if (!empty($constituency)) echo ' active';?>">
<h4>Search by link status</h4>
<input type="hidden" name="tables" value="<?php echo $tables ?>"/>

<p>Status: <input type="radio" name="constituency" value="constituent"<?php if ($constituency == 'constituent') echo " checked";?>/><label for="constituent">constituent</label> <input type="radio" name="constituency" value="multiple_constituents"<?php if ($constituency == 'multiple_constituents') echo " checked";?>/> <label for="multiple_constituents">multiple constituents</label> <input type="radio" name="constituency" value="not_constituent"<?php if ($constituency == 'not_constituent') echo " checked";?>/> <label for="not_constituent">not constituent</label></p>

<p>Failure type: <select name="failure">
	<option value=""></option>
	<option value="missing_before"<?php if ($failure == 'missing_before') echo " selected";?>>before</option>
	<option value="missing_after"<?php if ($failure == 'missing_after') echo " selected";?>>after</option>
	<option value="missing_before_after"<?php if ($failure == 'missing_before_after') echo " selected";?>>before_after</option>
	<option value="x_clausal"<?php if ($failure == 'x_clausal') echo " selected";?>>cross-clausal</option>
</select></p>
<p>Missing: <input type="search" name="missing_node"<?php if (!empty($missing_node)) echo " value='{$missing_node}'";?>/> Dominating: <input type="search" name="immediate_node"<?php if (!empty($immediate_node)) echo " value='{$immediate_node}'";?>/></p>

<p>
Output tree?
<input type="checkbox" name="tree"<?php
if (isset($_REQUEST['tree']))
        echo " checked";
?>>
</p>

<input type="submit" value="Search!"/> 
</form>

</div>

<h3>Results</h3>

<?php
$query = isset($_REQUEST['query']) && !empty($_REQUEST['query']) ?
           $_REQUEST['query'] :
           "(NP (NP (DT the ) <(JJS WORD ) NODE+ )> NODE+ )";

if ($full == 'stats') {
	$pattern = new Pattern($query);
	$results = $pattern->queryDb(-1);
	
	echo "<h4>SQL:</h4>\n<code>" . $pattern->prep->queryString . "<br>";
	echo "like is bound to " . $pattern->getLike() . "<br>";
	echo "</code>\n\n";
	
	$n = count($results);

	echo "<p>$n/98527 total results.";
	$pct = round($n*100/98527, 2);
	echo "($pct%)</p>";
	if (strpos($query, "{") !== false) {
		echo "<table>";
		foreach ($pattern->backrefs as $id => $instance) {
			foreach ($instance as $match) {
				echo "<tr><th>$id</th><td>" . implode('</td><td>', $match) . "</td></tr>\n";
			}
		}
		echo "</table>";
	}
	$time_end = microtime_float();
	$time = round($time_end - $time_start,2);
} else {
	// ajax, including link status queries
?>
<script>
$(function() {
	var tree = $('[name="tree"]').prop('checked');
	var query = $('#query').val();

	var tables = '<?php echo $tables; ?>';

	// parameters for link status search:
	var constituency = $('[name=constituency]:checked').val();
	var failure = $('[name=failure]').val();
	var missing = $('[name=missing_node]').val();
	var immediate = $('[name=immediate_node]').val();

	var results = $('#results');
	var startTime = new Date();
	var ajax = [];
	var returned = 0;
	var atATime = 500;
	var expected = 100000/atATime;
	var start = 0;
	var resultsCount = 0;
	
	function runAjax() {
		if (ajax.length - returned < 5) {
			start += atATime;
<?php if (empty($constituency)): ?>
			ajax.push($.getJSON('search_ajax.php', {query: query, tree: true, end: start + atATime, start: start, tables: tables }, showResults));
<?php else: ?>
			ajax.push($.getJSON('search_ajax.php', {constituency: constituency, failure: failure, missing: missing, immediate: immediate, tree: true, end: start + atATime, start: start, tables: tables }, showResults));
<?php endif; ?>
		}

		if (!(start < 100000))
			return;

		setTimeout(runAjax, 300); // at most one query per 300ms
	}
	runAjax();
	window.runAjax = runAjax;
	
	function showResults(json) {
		returned++;
		$('#status').text(Math.round((returned/expected) * 100) + '%');
		if (!'results' in json)
			return results.append('<p>error.</p>');

		resultsCount += json.results.length;
		$('#found').text(resultsCount + ' found');
		
		$.each(json.results, function() {
			var result = '<div id="' + this.id + '" class="result" data-treedata="' + escape(JSON.stringify(this.treedata)) + '"><h3>Entry <a href="display.php?entry=' + this.id + '">' + this.id + '</a>';
			var id = this.id;
			if (this.lid) {
				result += ' (';
				var links = [];
				$.each(this.lid.split(','), function(i, val) {
					links.push('<a href="display.php?entry=' + id + '&id=' + val + '">' + val + '</a>');
				});
				result += links.join(', ') + ')';
			}
			result += ' [<a href="http://metafilter.com/' + this.id + '/" target="_new">MeFi</a>]:</h3>';
			if (tree) {
				$.each(this.treedata, function() {
					result += '<span class="tree"><img src="lib/phpsyntaxtree/stgraph.svg?data=' + escape(this) + '"/></span>';
				});
			} else {
				result += '<span class="content">' + this.content + '</span>';
			}
			result += '</div>';
			results.append(result);
		});
		$('#time').text(((new Date()) - startTime)/1000);
	}
});
</script>
<?php
}
$time_end = microtime_float();
$time = round($time_end - $time_start,2);

echo "<p><span id='found'></span> (<span id='time'>$time</span> seconds, <span id='status'></span>)</p>";

?>
<div id="results">
</div>

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
