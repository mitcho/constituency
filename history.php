<?php
include('functions.php');
include('connect_mysql.php');
include('functions.display.php');

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
if (is_array($links)): 

	$groups = array();
	$group_count = 0;
	$group_start_time = false;
	$last_time = false;
	foreach ($links as $link) {
		$new_time = strtotime($link->date);
		if ( $last_time && abs($last_time - $new_time) > 60*10 ) {
			// record the last group, which ended at last_time
			$groups[] = array(
				'time' => abs($last_time - $group_start_time),
				'count' => $group_count
			);
			$group_count = 0;
			$group_start_time = false;
		}
			
		$group_count++;
		$last_time = $new_time;
		if ( !$group_start_time )
			$group_start_time = $last_time;
	}
	
	// compute average judging speed:
	$total_time = 0;
	$total_count = 0;
	foreach ( $groups as $group ) {
		$total_time += $group['time'];
		$total_count += $group['count'];
	}
	
	echo "<div><h3>Time spent judging:</h3><p>" . ($total_time / 60) . "s</p><h3>Average time to judge:</h3><p>" . ($total_time / $total_count / 60) . "s</p></div>";
?>
<ol class='history'>
<?php foreach ($links as $link): 
	// if there was more than 15 min since the last,
	if ( $last_time && abs($last_time - strtotime($link->date)) > 60*15 )
		echo "</ol><ol class='history'>";
	$last_time = strtotime($link->date);
?>
	<li><a href='display.php?entry=<?php echo (int) $link->entry; ?>&id=<?php echo (int) $link->id; ?>' data-placement='below' rel='twipsy' title='<?php echo esc_attr($link->date); ?>'>#<?php echo (int) $link->entry; ?>:<?php echo (int) $link->id; ?></a>: "...<?php echo $link->text; ?>..."</li>
<?php endforeach; ?>
</ol>
<?php endif; ?>

</div><!--/container-->
</body>
</html>
