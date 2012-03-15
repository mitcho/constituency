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
<?php head('reports', 'Reports: Nonconstituents by Type'); ?>
<body>
<?php nav('reports', 'nonconstituents-tags'); ?>

<div class="container" id='container'>
<?php
$links = $db->get_results('select basic.entry, basic.id, basic.content, basic.text, basic.href, tags.name, tags_xref.tid from (select * from (select lc.entry, lc.id, date, text, href, content, constituency from link_constituency as lc
left join links as l on (lc.entry = l.entry and lc.id = l.id)
join entries as e on (e.id = l.entry)
order by date desc) as t group by entry, id having constituency = "not_constituent") as basic
join tags_xref on (basic.entry = tags_xref.entry and basic.id = tags_xref.id)
join tags on (tags.id = tags_xref.tid and tags.constituency_specific = "not_constituent")
order by tags_xref.tid');
?>

<?php if (is_array($links)):

$type = 0;

foreach ($links as $link):
	if ( $type != $link->tid ) {
		if ( $type )
			echo "</ol>"; // close the last list

		$type = $link->tid;
		echo "<h2>" . esc_html($link->name) . "</h2>";
		echo "<ol>";
	}

	$text = formatDisplayEntry(wp_kses_data($link->content), $link->text, str_replace('&', '&amp;', $link->href));
?>
	<li><a href='display.php?entry=<?php echo (int) $link->entry; ?>&id=<?php echo (int) $link->id; ?>'>#<?php echo (int) $link->entry; ?>:<?php echo (int) $link->id; ?></a>: <?php echo $text; ?></li>
<?php
endforeach;
if ( $type )
	echo '</ol>';
endif; ?>

</div><!--/container-->
</body>
</html>
