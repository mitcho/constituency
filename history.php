<?php
include('functions.php');
include('functions.display.php');
include('connect_mysql.php');

// Comment out to avoid errors, etc. if not in debug mode.
$debug = isset($_GET['debug']);
$debugExtra = "";
if(!$debug)
	define('SUPPRESS_OUTPUT', true);
else
	$debugExtra = "&debug=true";
?><!DOCTYPE html>
<html>
<head>
<title>The &lt;a&gt;constituent&lt;/a&gt; Project | History: <?php echo esc_html(USERNAME); ?></title>
<link rel="stylesheet" href="http://twitter.github.com/bootstrap/1.4.0/bootstrap.min.css">
<link type="text/css" rel="stylesheet" href="display.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-twipsy.js"></script>
<script type="text/javascript">
$(function() {
$('a').twipsy();
});
</script>
<?php 
if ( stristr($_SERVER['HTTP_HOST'], 'mit.edu') !== false ):?>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-19567124-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<?php endif;?>
</head>
<body>
<div class="topbar">
  <div class="topbar-inner">
	<div class="container container-maybe-fluid">
	  <a class="brand" href="http://constituency.mit.edu/">&lt;a&gt;constituent&lt;/a&gt;</a>
	  <p class="pull-left" style='color: #ddd'>History for <?php echo USERNAME; ?></p>
	</div>
  </div>
</div>

<style>
body { counter-reset: item; }
li { display: block }
li:before { content: counters(item, ".") ". "; counter-increment: item }
</style>

<div class="container" id='container'>
<?php
// @todo can't I just order and then group to get the latest constituency values for each, instead of subselect?
$links = $db->get_results('select * from (select lc.entry, lc.id, date, text, constituency from link_constituency as lc left join links as l on (lc.entry = l.entry and lc.id = l.id) where user = "'.USERNAME.'" order by date desc) as t group by entry, id order by date desc');

$lasttime = false;
if (is_array($links)): ?>
<ol>
<?php foreach ($links as $link): 
	// if there was more than 15 min since the last,
	if ( $lasttime && abs($lasttime - strtotime($link->date)) > 60*15 )
		echo "</ol><ol>";
	$lasttime = strtotime($link->date);
?>
	<li><a href='display.php?entry=<?php echo (int) $link->entry; ?>&id=<?php echo (int) $link->id; ?>' data-placement='below' rel='twipsy' title='<?php echo esc_attr($link->date); ?>'>#<?php echo (int) $link->entry; ?>:<?php echo (int) $link->id; ?></a>: "...<?php echo $link->text; ?>..."</li>
<?php endforeach; ?>
</ol>
<?php endif; ?>

</div><!--/container-->
</body>
</html>
