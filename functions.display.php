<?php

function formatDisplayEntry($content, $text, $url) {
	$content = splitSentences($content);
	$esc_link = preg_quote(trim($text), '!');
	$esc_url = preg_quote($url, '!');
	$esc_link = preg_replace('!\s!', '\s', $esc_link);
	$esc_link = str_replace('\.', '\.(\n?)', $esc_link);
	$regex = "!^.*<a href=['\"]{$esc_url}['\"]>\s*{$esc_link}\s*</a>.*$!im";

	preg_match($regex, $content, $match);
	if (isset($match[0]))
		$text = $match[0];

	// make the desired link colored.
	$text = preg_replace("!<a href=[\"']{$esc_url}[\"']!", '$0 class="desired-link"', $text);

	// process text slightly to remove unsightly things
	$text = trim($text);
	$text = preg_replace('/^<br>/', '', $text);
	
	return $text;
}

function permalink($entry, $id) {
	$args = array(
		'entry' => $entry,
		'id' => $id
	);
	if ( isset($_GET['debug']) )
		$args['debug'] = true;
	if ( isset($_GET['tables']) )
		$args['tables'] = $_GET['tables'];
	
	return 'display.php?' . http_build_query($args);
}

function randomLink($random) {
	if (!$random)
		$random = 'true'; // just for randomLink purposes
	$args = array('random' => $random);
	if ( isset($_GET['debug']) )
		$args['debug'] = 1;
	if ( isset($_GET['tables']) )
		$args['tables'] = $_GET['tables'];
	
	return 'display.php?' . http_build_query($args);
}

// $dir == 'next' or 'prev'
function getNextPrevLink( $entry, $id, $dir ) {
	global $db;
	
	$compare = $dir == 'next' ? '>' : '<';
	$order = $compare == '>' ? 'asc' : 'desc';
	$result = $db->get_row("select entry, id from " . LINKS_TABLE . " where entry $compare $entry or (entry = $entry and id $compare $id) order by entry $order, id $order limit 1");

	if ( $result === false || empty($result) )
		return false;
	
	return permalink($result->entry, $result->id);
}

function head($type, $title) {
?>
<head>
<title>The &lt;a&gt;constituent&lt;/a&gt; Project | <?php echo $title; ?></title>
<link rel="stylesheet" href="http://twitter.github.com/bootstrap/1.4.0/bootstrap.min.css">
<link type="text/css" rel="stylesheet" href="display.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-tabs.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-dropdown.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-alerts.js"></script>
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-twipsy.js"></script>
<?php if ( file_exists("$type.js") ) {
	echo "<script type='text/javascript' src='$type.js'></script>";
} ?>
<script type="text/javascript">
$(function() {
	$('.tabs').tabs();
	$('.topbar').dropdown();
	$('[rel=twipsy], a').twipsy();
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
<?php
}

function nav($type, $subtype = false) {
	global $random, $id, $entry, $random_tag_label, $parse_type, $db;
?>
<div class="topbar">
  <div class="topbar-inner">
	<div class="container">
	  <a class="brand" href="http://constituency.mit.edu/">&lt;a&gt;constituent&lt;/a&gt;</a>

	  <ul class="nav">
		<li class="dropdown"<?php if ( $type == 'reports' ) echo " class='active'";?>>
			<a class="dropdown-toggle" href="#">Reports</a>
			<ul class="dropdown-menu">
				<li<?php if ( $type == 'reports' && $subtype == 'nonconstituents' ) echo ' class="active"'; ?>><a href="nonconstituents.php">Nonconstituents</a></li>
			</ul>
		</li>
	  	<li<?php if ( $type == 'history' ) echo " class='active'";?>><a href='history.php' title='User history'>History</a></li>

		<li class="divider-vertical"></li>

		<?php if ( $type == 'display' ): ?>
	  	<li<?php if ( $type == 'display' && !$random ) echo " class='active'";?>><a href='<?php echo esc_url(permalink($entry, $id)); ?>' title='<?php echo esc_attr("Entry #$entry, link #$id"); ?>'>#<?php echo $entry; ?>:<?php echo $id; ?></a></li>
	  	<?php endif; ?>
	  	
		<li<?php if ( $random ) echo " class='active'";?>><a id='random-link' href="<?php echo randomLink($random); ?>"><span class="accelerator">(R)</span> Random<?php echo $random_tag_label; ?></a></li>
	  </ul>

	  <p class="pull-left">Logged in as <?php echo USERNAME; ?></p>

	  <ul class='nav secondary-nav'>
<?php if ( $type == 'display' ): ?>
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
<?php endif; ?>
	  </ul>
	</div>
  </div>
</div>
<?php
}