<?php

function formatDisplayEntry($content, $text, $href) {
	$prePattern = preg_quote($href) . '\V+' . preg_quote($text);
	$prePattern = preg_replace("!(/)!", '\/', $prePattern);
	$pattern = '/\V*' . $prePattern . '\V*/';
	preg_match($pattern, $content, $match);
	if(isset($match[0]))
		$text = $match[0];

	// make the desired link colored.
	$aPattern = '<a href=["\']' . preg_quote($href) . '["\']';
	$aPattern = preg_replace("!(/)!", '\/', $aPattern);
	$text = preg_replace("/$aPattern/", '$0 class="desired-link"', $text);

	// process text slightly to remove unsightly things
	$text = trim($text);
	$text = preg_replace('/^<br>/', '', $text);
	
	return $text;
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
	$args = array('random' => true);
	if ( isset($_GET['debug']) )
		$args['debug'] = 1;
	if ( isset($_GET['tables']) )
		$args['tables'] = $_GET['tables'];
	
	return 'display.php?' . http_build_query($args);
}