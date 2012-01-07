<?php
include("connect_mysql.php");
include("functions.php");

$args = parseArgs(isset($argv) ? $argv : array());
extract($args);

$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

$entry = mysql_real_escape_string($entry);
$id = mysql_real_escape_string($id);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>The &lt;a&gt;constituent&lt;/a&gt; Project | Display</title>
<link type="text/css" rel="stylesheet" href="display.css" />
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

$user = mysql_real_escape_string(get_current_user());

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
		$stanford = mysql_real_escape_string(oneParsePerLine($_POST['stanford']));
		$constituency = mysql_real_escape_string($_POST['constituency']);
		$failure_type = mysql_real_escape_string($_POST['failure_type']);
		$entry_annotation = mysql_real_escape_string($_POST['entry_annotation']);
		$link_annotation = mysql_real_escape_string($_POST['link_annotation']);

		$q1 = mysql_query("update " . ENTRIES_TABLE . " set stanford = '$stanford', annotation = '$entry_annotation', modified_by = '$user' where id = $entry");
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
if($random) {
	$results = mysql_query("select entry, id from " . LINKS_TABLE . " order by RAND() limit 1");
	$row = mysql_fetch_array($results);
	$entry = $row['entry'];
	$id = $row['id'];
}

if ($id !== '' && $entry !== '') {
	$results = mysql_query("select e.stanford, e.content, e.annotation, l.text, l.href, l.constituency, l.failure_type, l.lannotation from " . ENTRIES_TABLE . " as e join " . LINKS_TABLE . " as l where e.id = $entry and l.entry = $entry and l.id = $id");
	$row = mysql_fetch_array($results);
	$constituency = $row['constituency'];
	$failureType = $row['failure_type'];
	$entryAnnotation = htmlspecialchars($row['annotation']);
	$linkAnnotation = htmlspecialchars($row['lannotation']);

	$fulltext = $row['content'];
	$prePattern = preg_quote($row['href']) . '\V+' . preg_quote($row['text']);
	$prePattern = preg_replace("!(/)!", '\/', $prePattern);
	$pattern = '/\V*' . $prePattern . '\V*/';
	preg_match($pattern, $fulltext, $match);
	if(isset($match[0]))
		$text = $match[0];

	// make the desired link colored.
	$aPattern = '<a href=["\']' . preg_quote($row['href']) . '["\']';
	$aPattern = preg_replace("!(/)!", '\/', $aPattern);
	$text = preg_replace("/$aPattern/", '$0 class="desired-link"', $text);

	// process text slightly to remove unsightly things
	$text = trim($text);
	$text = preg_replace('/^<br>/', '', $text);

	$parse = $row['stanford'];
	$treePattern = getPattern($row['text']);
	$treePattern = '/\V*' . $treePattern . '\V*/';
	preg_match($treePattern, $parse, $match);

	if(isset($match[0]))
		$tree = $match[0];
	$imageData = htmlspecialchars(str_replace('"', '\\"', formatParseTree($tree)));
	
	$results = mysql_query("select tid from " . TAGS_TABLE . " where entry = $entry and lid = $id");
	while($row = mysql_fetch_array($results))
		$tags[$row['tid']]['enabled'] = true;
}

// Generates a list of <option> tags with the tag named $value starred.
function generateOptions($optionKeys, $value) {
	$names = array_keys($optionKeys);
	foreach ($names as $name) {
		echo "<option id=\"option-{$optionKeys[$name]}\" value=\"$name";
		if($name == $value)
			echo '" selected="selected">*';
		else
			echo '">';
		echo "<span class=\"accelerator\">({$optionKeys[$name]})</span> $name</option>\n";
	}
}

// Prints the tag checkboxes.
function printTags($tags, $tagKeys) {
	$tids = array_keys($tags);
	// use separate indexing so key events are easier.
	$i = 0; // human
	$j = 0; // machine
	foreach($tids as $tid) {
		$tagDetails = $tags[$tid];
		$human = $tagDetails['human'] ? '' : ' disabled="disabled"';
		$idAppend = $tagDetails['human'] ? $i : "machine-$j";
		$labelClass = $tagDetails['human'] ? 'tag-label-human' : 'tag-label-machine';
		$number = $tagDetails['human'] ? "<span class=\"accelerator\">({$tagKeys[$i]})</span> " : '';
		$enabled = $tagDetails['enabled'] ? ' checked="checked"' : '';
		echo "<div><input type=\"checkbox\" name=\"tags[$tid]\" value=\"1\" id=\"tag-$idAppend\"$human$enabled />";
		echo "<label for=\"tag-$tid\" class=\"$labelClass\"> $number{$tagDetails['name']}</label></div>\n";
		if($tagDetails['human'])
			$i++;
		else
			$j++;
	}
}

function randomLink($random, $entry, $id) {
	$host = $_SERVER['HTTP_HOST'];
	$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

	$newget = $_GET;

	if($random) {
		$newget['entry'] = $entry;
		$newget['id'] = $id;
		unset($newget['random']);
	}
	else {
		$newget['random'] = 'true';
		unset($newget['entry']);
		unset($newget['id']);
	}

	return "http://$host$uri/display.php?" . http_build_query($newget);
}
?>

<div><a id="random-link" href="<?php echo randomLink($random, $entry, $id); ?>"><span class="accelerator">(M)</span> <?php echo $random ? 'Permalink' : 'Random'; ?></a></div>

<h2>Entry #<?php echo $entry; ?>, link #<?php echo $id; ?> in tables "<?php echo $tables; ?>":</h2>

<p><?php echo $text; ?></p>

<?php echo "<form action=\"display.php?entry=$entry&id=$id&tables=$tables$debugExtra\" method=\"POST\">"; ?>
<div id="container">
<div id="image">
<?php echo '<img src="lib/phpsyntaxtree/stgraph.svg?data=' . $imageData . '" alt="Tree: ' . $imageData . "\" />\n"; ?>
</div>

<div id="parse-box">
<textarea id="parse" rows="10" cols="30" name="stanford" wrap="off">
<?php echo htmlspecialchars(trim(retabTree($parse, "  "))); ?>
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

<script type="text/javascript" src="analytics.js"></script>
<script type="text/javascript" src="display.js"></script>
</body>
</html>
