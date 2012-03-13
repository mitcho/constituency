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
<?php head('reports', 'Reports: Nonconstituents'); ?>
<body>
<?php nav('reports', 'nonconstituents'); ?>

<div class="container" id='container'>
<?php
// @todo can't I just order and then group to get the latest constituency values for each, instead of subselect?
$links = $db->get_results('select basic.*, group_concat(tags_xref.tid) as tag_ids, group_concat(tags.name separator ", ") as tag_names from (select * from (select lc.entry, lc.id, date, text, href, content, constituency from link_constituency as lc
left join links as l on (lc.entry = l.entry and lc.id = l.id)
join entries as e on (e.id = l.entry)
order by date desc) as t group by entry, id having constituency = "not_constituent") as basic
left join tags_xref on (basic.entry = tags_xref.entry and basic.id = tags_xref.id)
left join tags on (tags.id = tags_xref.tid)
group by basic.entry, basic.id');
?>

<?php if (is_array($links)): ?>
<style>
.desired-link {
	text-decoration: underline;
}
</style>
<ol>
<?php foreach ($links as $link):
	// If subword, skip.
	if ( in_array( '14', explode(',', $link->tag_ids) ) )
		continue;

	// If nonlinguistic, skip.
	if ( in_array( '12', explode(',', $link->tag_ids) ) )
		continue;

	$text = formatDisplayEntry(wp_kses_data($link->content), $link->text, str_replace('&', '&amp;', $link->href));
?>
	<li><a href='display.php?entry=<?php echo (int) $link->entry; ?>&id=<?php echo (int) $link->id; ?>' data-placement='below' rel='twipsy' title='<?php echo esc_attr($link->tag_names); ?>'>#<?php echo (int) $link->entry; ?>:<?php echo (int) $link->id; ?></a>: <?php echo $text; ?></li>
<?php endforeach; ?>
</ol>
<?php endif; ?>

</div><!--/container-->
</body>
</html>
