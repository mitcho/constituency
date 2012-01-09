<?php
include('functions.php');
include('functions.display.php');
include('connect_mysql.php');

$args = parseArgs(isset($argv) ? $argv : array());
extract($args);

$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

$entry = (int) $entry;
$id = (int) $id;
?>
<!DOCTYPE html>
<html>
<head>
<title>The &lt;a&gt;constituent&lt;/a&gt; Project | Display</title>
<link type="text/css" rel="stylesheet" href="display.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
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
<?php
// Comment out to avoid errors, etc. if not in debug mode.
$debug = isset($_GET['debug']);
$debugExtra = "";
if(!$debug)
	define('SUPPRESS_OUTPUT', true);
else
	$debugExtra = "&debug=true";

$constituencyValues = array("" => "Q", "constituent" => "W", "not_constituent" => "E", "multiple_constituents" => "R", "error" => "T");
$failureValues = array("" => "Y", "missing_before" => "U", "missing_after" => "I", "missing_before_after" => "O", "x_clausal" => "P");
$tagKeys = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');

$skipNoLinkEntries = false;

$text = "Error.";
$parse = "Error.";
$tree = "Error.";
$imageData = "[ERR Error.]";
$constituency = "";
$failureType = "";
$tags = array();
$tagged = array();

$results = mysql_query("select * from tags");
while($row = mysql_fetch_array($results))
	$tags[$row['id']] = array('name' => $row['name'], 
	                          'enabled' => false, 
							  'human' => $row['human'] ? true : false);

// default id is 0
if ($entry !== '' && $id === '') {
	$id = 0;
}

if ($id !== '' && $entry !== '') {
	$results = mysql_query("select tid from " . TAGS_TABLE . " where entry = $entry and lid = $id");
	while($row = mysql_fetch_array($results))
		$tags[$row['tid']]['enabled'] = true;

	// Update if we received new code.
	if(isset($_POST) && count($_POST) > 0) {
		$constituency = mysql_real_escape_string($_POST['constituency']);
		$failure_type = mysql_real_escape_string($_POST['failure_type']);
		$entry_annotation = mysql_real_escape_string($_POST['entry_annotation']);
		$link_annotation = mysql_real_escape_string($_POST['link_annotation']);

		$q1 = mysql_query("update " . ENTRIES_TABLE . " set annotation = '$entry_annotation', modified_by = '$user' where id = $entry");
		$q2 = mysql_query("update " . LINKS_TABLE . " set constituency = '$constituency', lannotation = '$link_annotation', modified_by = '$user' where entry = $entry and id = $id");
		$q3 = true;
		if($constituency != "constituent")
			$q3 = mysql_query("update " . LINKS_TABLE . " set failure_type = '$failure_type', modified_by = '$user' where entry = $entry and id = $id");
		if(!($q1 && $q2 && $q3))
			echo mysql_error();
		
		$tids = array_keys($tags);
		if(!isset($_POST['tags']))
			$_POST['tags'] = array();
		$checkedTags = $_POST['tags'];
		foreach($tids as $tid) {
			if(isset($checkedTags[$tid]) && !$tags[$tid]['enabled']) {
				addTag($entry, $id, $tid);
				$tags[$tid]['enabled'] = true;
			}
			else if(!isset($checkedTags[$tid]) && $tags[$tid]['enabled']) {
				delTag($entry, $id, $tid);
				$tags[$tid]['enabled'] = false;
			}
		}
	}

	// If that was an asynchronous update, end.
	if(isset($_POST['async'])) {
		echo "</body>\n</html>";
		exit(0);
	}
}

// wait until updates are done to change $entry and $id
if ($random) {
	$results = $db->get_row( "select entry, id from " . LINKS_TABLE . " order by RAND() limit 1", ARRAY_A );
	extract($results);
}

if ($id !== '' && $entry !== '') {
	extract($db->get_row("select content, text, href from entries as e join links as l on e.id = l.entry where e.id = $entry and l.id = $id", ARRAY_A));
	// @todo e.annotation, l.constituency, l.failure_type, l.lannotation
	//$constituency = $row['constituency'];
	//$failureType = $row['failure_type'];
	//$entryAnnotation = htmlspecialchars($row['annotation']);
	//$linkAnnotation = htmlspecialchars($row['lannotation']);

	$text = formatDisplayEntry($content, $text, $href);
	
	$results = mysql_query("select tid from " . TAGS_TABLE . " where entry = $entry and lid = $id");
	while($row = mysql_fetch_array($results))
		$tags[$row['tid']]['enabled'] = true;
}
?>

<div><a id="random-link" href="<?php echo randomLink($random, $entry, $id); ?>"><span class="accelerator">(M)</span> <?php echo $random ? 'Permalink' : 'Random'; ?></a></div>

<h2>Entry #<?php echo $entry; ?>, link #<?php echo $id; ?> in tables "<?php echo $tables; ?>":</h2>

<p><?php echo $text; ?></p>

<?php echo "<form action=\"display.php?entry=$entry&id=$id&tables=$tables$debugExtra\" method=\"POST\">"; ?>
<div id="container">
<div id="image">
</div>

<div id="parse-box">
<textarea id="parse" rows="10" cols="30" name="stanford" wrap="off">
</textarea>
</div>
</div>

<div id="annotation-box">
<div>Entry: <input type="text" name="entry_annotation" value="<?php echo $entryAnnotation; ?>"/></div>
<div>Link: <input type="text" name="link_annotation" value="<?php echo $linkAnnotation; ?>"/></div>
</div>

<h3>Link Tags:</h3>
<div id="tags-box">
<div id="tags-padder">
<?php printTags($tags, $tagKeys); ?>
</div>
</div>

<div id="buttons">
<select name="constituency" id="constituency_select">
<?php generateOptions($constituencyValues, $constituency); ?>
</select>
<select name="failure_type" id="failure_select">
<?php generateOptions($failureValues, $failureType); ?>
</select>
<span id="before-submit"></span><input type="submit" id="submit" />
</div>

<input type="hidden" id="random" value="<?php echo $random ? 'true' : 'false'; ?>" />
<input type="hidden" id="entry" value="<?php echo $entry; ?>" />
<input type="hidden" id="id" value="<?php echo $id; ?>" />
<input type="hidden" id="tables" value="<?php echo $tables; ?>" />
</form>

</body>
</html>
