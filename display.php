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

$args = parseArgs(isset($argv) ? $argv : array());
extract($args);

$entry = (int) $entry;
$id = (int) $id;

if ( empty($id) || empty($entry) || $random ) {
	header('Location: ' . randomLink());
	exit;
}

$data = $db->get_row("select content, text, href from entries as e join links as l on e.id = l.entry where e.id = $entry and l.id = $id", ARRAY_A);

// if can't find data
if ( is_null($data) ) {
	echo '<div class="alert-message error"><p>Could not find data. :(</p></div>';
	echo '</div></body></html>';
	exit;
}

extract($data);
// @todo e.annotation, l.lannotation
//$entryAnnotation = htmlspecialchars($row['annotation']);
//$linkAnnotation = htmlspecialchars($row['lannotation']);

$text = formatDisplayEntry(wp_kses_data($content), $text, str_replace('&', '&amp;', $href));

?><!DOCTYPE html>
<html>
<?php head('display', 'Display'); ?>
<body>
<?php nav('display'); ?>

<div class="container" id='container'>

<div class="row" id="nextprev">
<?php
$prev = getNextPrevLink($entry, $id, 'prev', $filter_tag);
$next = getNextPrevLink($entry, $id, 'next', $filter_tag);
?>
<div class="pull-left <?php if (!$prev) echo ' disabled';?>"><a id='prev' href="<?php echo esc_url($prev);?>">&larr; Previous <span class='accelerator'>(J)</span></a></div>
<div class="pull-right <?php if (!$next) echo ' disabled';?>"><a id='next' href="<?php echo esc_url($next);?>"><span class='accelerator'>(K)</span> Next &rarr;</a></div>
</div>

<div id='entry' class='well'><?php echo $text; ?></div>

<form action="display.php?entry=<?php echo $entry; ?>&id=<?php echo $id; ?>&parse_type=<?php echo $parse_type . $debugExtra; ?>" method='POST'>

<div id='parse-container'>
	<ul class="tabs">
	<li class="active"><a href="#classification">Classification</a></li>
	<li><a href="#image">Tree</a></li>
	<li><a href="#parse-box">Brackets</a></li>
	</ul>
	 
	<div class="pill-content">
	<div class="active" id="classification"></div>
	<div id="image"></div>
	<div id="parse-box"><textarea id="parse" rows="10" cols="30" name="stanford" wrap="off" spellcheck='false'></textarea></div>
	</div>
</div>

<div class='row'>
<div class='span-one-third'>
	<h4>Tags</h4>
	<ul class='inputs-list' id='tags'>
<?php
$tags = $db->get_results("select *, (tid is not null) as checked from tags left join tags_xref on (tags.`id` = tags_xref.tid and entry = $entry and lid = $id) where parse_specific = 0");
foreach ($tags as $tag) {
	$twipsy = '';
	if ( $tag->user )
		$twipsy = " data-placement='below' rel='twipsy' title='" . esc_attr($tag->user) . "'";
	$disabled = ($tag->human == 1 ? '' : ' class="disabled" disabled="disabled"');
	echo "<li><label $disabled><input $disabled type='checkbox' name='tags[{$tag->id}]' data-tag='{$tag->id}' id='tag-{$tag->id}' " . ($tag->checked == 1 ? ' checked="checked"' : '') . "/> <span$twipsy>" . esc_html($tag->name) . "</span></label></li>";
}
?>
	</ul>
</div>
<!--<div class='span-one-third'>
	<h4>Entry<h4>
	<textarea name="entry_annotation" class='annotation'><?php echo $entryAnnotation; ?></textarea>
</div>
<div class='span-one-third'>
	<h4>Link</h4>
	<textarea name="link_annotation" class='annotation'><?php echo $linkAnnotation; ?></textarea>
</div>-->
</div>

<div class="well" style="min-height:40px;">
<?php
$verdict = $db->get_row("select * from link_constituency where id = $id and entry = $entry order by date desc limit 1");

$constituency = $verdict ? $verdict->constituency : '';
$modified = $verdict ? 'Modified: ' . date('Y-m-d', strtotime($verdict->date)) . " by {$verdict->user}" : '';

?>
<div class="pull-left">
	<span class='btn submit large<?php if (!$verdict) echo ' success'; if ($constituency == 'constituent') echo ' selected'; ?>' id='constituent'>Constituent! <span class='accelerator'>(C)</span></span>
	<span class='btn submit large<?php if (!$verdict) echo ' danger'; if ($constituency == 'not_constituent') echo ' selected'; ?>' id='not_constituent'>Not constituent! <span class='accelerator'>(N)</span></span>
	<span class='btn submit small'>Just save tags <span class='accelerator'>(S)</span></span>
	<p id='last_modified' style='display:inline'><small><?php echo esc_html($modified); ?></small></p>
</div>
<div class="pull-right"><span id="spinner" style='display:none'><img src='spinner.gif'/></span></div>
</div>

<input type="hidden" id="random" value="<?php echo $random ? 'true' : 'false'; ?>" />
<input type="hidden" id="entry" value="<?php echo $entry; ?>" />
<input type="hidden" id="id" value="<?php echo $id; ?>" />
<input type="hidden" id="parse_type" value="<?php echo $parse_type; ?>" />
</form>

<div class="modal fade" id="options">
  <div class="modal-header">
    <a class="close" data-dismiss="modal">&times;</a>
    <h3>Options</h3>
  </div>
  <div class="modal-body">
	<div class='clearfix'>
	Auto-advance: <input type="checkbox" id="move_forward"/>
	</div>

	<div class='clearfix'>
	Filters:
	<select id="filter_tag">
		<option value="0">No filter</option>
	<?php foreach ( $db->get_results("select * from tags where parse_specific = 0") as $tag ):?>
		<option value="<?php echo esc_attr($tag->id); ?>"><?php echo esc_html($tag->name); ?></option>
	<?php endforeach; ?>
	</select>
	<select id="filter_constituency">
		<option value="0">No filter</option>
		<option value="constituent">Constituent</option>
		<option value="not_constituent">Not constituent</option>
		<option value="unjudged">Unjudged</option>
	</select>
	</div>
  </div>
  <!--<div class="modal-footer">
    <a href="#" class="btn btn-primary">Save changes</a>
    <a href="#" class="btn">Close</a>
  </div>-->
</div>

</div><!--/container-->
</body>
</html>
