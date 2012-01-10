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

// Update
if ( $entry && isset($_POST) && !empty($_POST) ) {
	$entry_annotation = mysql_real_escape_string($_POST['entry_annotation']);
	$link_annotation = mysql_real_escape_string($_POST['link_annotation']);

	// $q1 = mysql_query("update " . ENTRIES_TABLE . " set annotation = '$entry_annotation', modified_by = '$user' where id = $entry");
	// $q2 = mysql_query("update " . LINKS_TABLE . " set constituency = '$constituency', lannotation = '$link_annotation', modified_by = '$user' where entry = $entry and id = $id");
	$q3 = true;
	if($constituency != "constituent")
		$q3 = mysql_query("update " . LINKS_TABLE . " set failure_type = '$failure_type', modified_by = '$user' where entry = $entry and id = $id");
	if(!($q1 && $q2 && $q3))
		echo mysql_error();
	
	// If that was an asynchronous update, end.
	if(isset($_POST['async'])) {
		echo "</body>\n</html>";
		exit(0);
	}
}

// default id is 0
if ( empty($id) ) {
	$id = 0;
}

// wait until updates are done to change $entry and $id
if ( $random || ($id === 0 && empty($entry)) ) {
	$results = $db->get_row( "select entry, id from " . LINKS_TABLE . " order by RAND() limit 1", ARRAY_A );
	extract($results);
	$random = 1;
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

$text = formatDisplayEntry($content, $text, $href);

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
<script type="text/javascript" src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-alerts.js"></script>
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
		<li<?php if ( $random ) echo " class='active'";?>><a href="<?php echo randomLink($random); ?>"><span class="accelerator">(M)</span> Random</a></li>
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

<div id='entry'><?php echo $text; ?></div>

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
<div class='span8'>
	<h4>Tags</h4>
<?php
$tags = $db->get_results("select * from tags left join tags_xref on (tags.`id` = tags_xref.tid and entry = $entry and lid = $id) where human = 1 and parse_specific = 0");
foreach ($tags as $tag) {
	
}
?>
</div>
<div class='span4'>
	<h4>Entry<h4>
	<textarea name="entry_annotation"><?php echo $entryAnnotation; ?></textarea>
</div>
<div class='span4'>
	<h4>Link</h4>
	<textarea name="link_annotation"><?php echo $linkAnnotation; ?></textarea>
</div>
</div>

<div class="row">
<div class="pull-right"><span id="before-submit"></span><input type="submit" id="submit" class='btn primary' value='Save!' /></div>
</div>

<input type="hidden" id="random" value="<?php echo $random ? 'true' : 'false'; ?>" />
<input type="hidden" id="entry" value="<?php echo $entry; ?>" />
<input type="hidden" id="id" value="<?php echo $id; ?>" />
<input type="hidden" id="parse_type" value="<?php echo $parse_type; ?>" />
</form>

</div><!--/container-->
</body>
</html>
