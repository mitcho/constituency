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

$args = parseArgs(isset($argv) ? $argv : array());
extract($args);

$entry = (int) $entry;
$id = (int) $id;
$random_tag_label = '';

// default id is 0
if ( empty($id) ) {
	$id = 0;
}

// wait until updates are done to change $entry and $id
if ( $random && is_numeric($random) ) {
	$random = (int) $random;

	$random_tag = $db->get_var( "select name from tags where id = {$random}" );
	// if the tag exists
	if ( $random_tag ) {
		$results = $db->get_row( "select links.entry, links.id from links join tags_xref on (links.entry = tags_xref.entry and links.id = tags_xref.lid) where tid = {$random} order by RAND() limit 1", ARRAY_A );
		extract($results);
		
		$random_tag_label = " ($random_tag)";
	}
}

if ( ($id === 0 && empty($entry)) || ($random && empty($random_tag_label)) ) {
	$results = $db->get_row( "select entry, id from " . LINKS_TABLE . " order by RAND() limit 1", ARRAY_A );
	extract($results);
	$random = 'true';
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

?>
<!DOCTYPE html>
<html>
<head>
<title>The &lt;a&gt;constituent&lt;/a&gt; Project | Display</title>
<link rel="stylesheet" href="http://twitter.github.com/bootstrap/1.4.0/bootstrap.min.css">
<link type="text/css" rel="stylesheet" href="display.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-tabs.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-dropdown.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-alerts.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-twipsy.js"></script>
<script type="text/javascript" src="display.js"></script>
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
	  <ul class="nav">
	  	<li<?php if ( !$random ) echo " class='active'";?>><a href='<?php echo esc_url(permalink($entry, $id)); ?>' title='<?php echo esc_attr("Entry #$entry, link #$id"); ?>'>#<?php echo $entry; ?>:<?php echo $id; ?></a></li>
		<li<?php if ( $random ) echo " class='active'";?>><a id='random-link' href="<?php echo randomLink($random); ?>"><span class="accelerator">(R)</span> Random<?php echo $random_tag_label; ?></a></li>
	  	<li><a href='history.php' title='User history'>History</a></li>
	  </ul>
	  <p class="pull-left">Logged in as <?php echo USERNAME; ?></p>
	  <ul class='nav secondary-nav'>
	  	<li><a id='toggleWidth' href='#'>Wide</a></li>
		<li class="dropdown" id='parse-control'>
			<a class="dropdown-toggle" href="#">Parse: <?php echo $parse_type ? esc_html($parse_type) : 'None'; ?></a>
			<ul class="dropdown-menu">
				<?php foreach ( $db->get_col('select type from parses group by type') as $possible_type ): ?>
				<li<?php if ( $possible_type === $parse_type ) echo ' class="active"'; ?> data-parse_type='<?php echo esc_attr($possible_type); ?>'><a href="#"><?php echo esc_html($possible_type); ?></a></li>
				<?php endforeach; ?>
				<li class="divider"></li>
				<li<?php if ( false === $parse_type ) echo ' class="active"'; ?> data-parse_type=''><a href="#">None</a></li>
			</ul>
		</li>
	  </ul>
	</div>
  </div>
</div>

<div class="container container-maybe-fluid" id='container'>

<div class="row" id="nextprev">
<?php
$prev = getNextPrevLink($entry, $id, 'prev');
$next = getNextPrevLink($entry, $id, 'next');
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
	<span class='btn submit small'>Just save tags and comments</span>
	<p id='last_modified' style='display:inline'><small><?php echo esc_html($modified); ?></small></p>
</div>
<div class="pull-right"><span id="spinner" style='display:none'><img src='spinner.gif'/></span></div>
</div>

<input type="hidden" id="random" value="<?php echo $random ? 'true' : 'false'; ?>" />
<input type="hidden" id="entry" value="<?php echo $entry; ?>" />
<input type="hidden" id="id" value="<?php echo $id; ?>" />
<input type="hidden" id="parse_type" value="<?php echo $parse_type; ?>" />
</form>

</div><!--/container-->
</body>
</html>
