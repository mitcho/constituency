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