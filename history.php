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
<?php head('history', 'History for ' . esc_html(USERNAME)); ?>
<body>
<?php nav('history'); ?>

<style>
#container { counter-reset: item; }
.history li { display: block }
.history li:before { content: counters(item, ".") ". "; counter-increment: item }
</style>

<div class="container" id='container'>
<?php
// @todo can't I just order and then group to get the latest constituency values for each, instead of subselect?
$links = $db->get_results('select * from (select lc.entry, lc.id, date, text, constituency from link_constituency as lc left join links as l on (lc.entry = l.entry and lc.id = l.id) where user = "'.USERNAME.'" order by date desc) as t group by entry, id order by date desc');

$lasttime = false;
if (is_array($links)): ?>
<ol class='history'>
<?php foreach ($links as $link): 
	// if there was more than 15 min since the last,
	if ( $lasttime && abs($lasttime - strtotime($link->date)) > 60*15 )
		echo "</ol><ol class='history'>";
	$lasttime = strtotime($link->date);
?>
	<li><a href='display.php?entry=<?php echo (int) $link->entry; ?>&id=<?php echo (int) $link->id; ?>' data-placement='below' rel='twipsy' title='<?php echo esc_attr($link->date); ?>'>#<?php echo (int) $link->entry; ?>:<?php echo (int) $link->id; ?></a>: "...<?php echo $link->text; ?>..."</li>
<?php endforeach; ?>
</ol>
<?php endif; ?>

</div><!--/container-->
</body>
</html>
