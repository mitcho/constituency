<?php

include('backpress/bp-support.php');
include('backpress/class.bpdb.php');
include('backpress/functions.core.php');
include('backpress/functions.formatting.php');
include('backpress/functions.plugin-api.php');
include('backpress/functions.kses.php');

$allowedtags = array(
	'a' => array(
		'href' => array (),
		'title' => array ()),
	'abbr' => array(
		'title' => array ()),
	'acronym' => array(
		'title' => array ()),
	'b' => array(),
	'blockquote' => array(
		'cite' => array ()),
	'br' => array(),
	'cite' => array (),
	'code' => array(),
	'del' => array(
		'datetime' => array ()),
	'dd' => array(),
	'dl' => array(),
	'dt' => array(),
	'em' => array (), 'i' => array (),
	'ins' => array('datetime' => array(), 'cite' => array()),
	'li' => array(),
	'ol' => array(),
	//	'p' => array(),
	'q' => array(
		'cite' => array ()),
	'strike' => array(),
	'strong' => array(),
	'sub' => array(),
	'sup' => array(),
	'u' => array(),
	'ul' => array(),
);

define("ENTRIES_TABLE", "entries");
define("LINKS_TABLE", "links");
define("TAGS_TABLE", "tags_xref");

if (isset($_SERVER['SSL_CLIENT_S_DN_Email']))
	define('USERNAME',strtolower(trim($_SERVER['SSL_CLIENT_S_DN_Email'])));
else
	define('USERNAME',get_current_user());

// Known terminal node labels:
// PTB says 'NP', 'NPS', 'PP' are terminals, but they're not in our corpus (???)
// For some reason in our db, possessive pronouns are PRP$, not PP$.
$terminalNodeLabels = array(
	'CC', 'CD', 'DT', 'EX', 'FW', 'IN', 'JJ', 'JJR', 'JJS', 'LS', 'MD', 'NN', 'NNS', 'PDT', 'POS', 'PP', 'PP$', 'RB', 'RBR', 'RBS', 'RP', 'SYM', 'TO', 'UH', 'VB', 'VBD', 'VBG', 'VBN', 'VBP', 'VBZ', 'WDT', 'WP', 'WP$', 'WRB',
	'PRP$'
);

define("PARSER_DIR", dirname(__FILE__) . "/lib/stanford-parser-2010-07-09");
define("DOT_TIMEOUT", 100000);
define("PUNCTUATION", ",|\\.|!|\\?|-LRB-|-RRB-|:|;|'|\"|\\\\$|``|#");

$getPatternCache = array('nopunc' => array(), 'greedy' => array());
$wordsCache = array();

// This is the list of command-line arguments and number of inputs for judge_constituency and parse_entries.
// They share a paradigm, so it makes sense that they also share features.
$definedArgs = array(
	'-rerun' => 1,
	'-range' => 2,
	'-random' => 0,
	'-dryrun' => 0, 
	'-data' => 1,
	'-entry' => 1,
	'-id' => 1,
	'-parse_type' => 1, 
	'-next' => 0, 
	'-previous' => 0);

function getPattern($link, $punctuationPass = false, $secondPass = false) {
	global $getPatternCache, $wordsCache, $web, $break, $split;
	
	$pp = $punctuationPass ? 'nopunc' : 'greedy';
	if (isset($getPatternCache[$pp][$link]) &&
	  strlen($getPatternCache[$pp][$link]))
		return $getPatternCache[$pp][$link];
	
	if (!$punctuationPass) {
		$result = tokenize($link);
		$wordsCache[$link] = $result;
		if ($result == "SENTENCE_SKIPPED_OR_UNPARSABLE\n") {
			if ($secondPass)
				return false;
			return getPattern($link, $punctuationPass, true);
		}
	} else {
		$result = $wordsCache[$link];
		// Remove all standalone punctuation
		$result = preg_replace("/(^|\s)(?:" . PUNCTUATION . ")(\s|$)/", ' ', $result);
		$result = trim($result);
	}
	
	if(!defined('SUPPRESS_OUTPUT')) {
		if ($web)
			echo preg_replace("![\r\n]!", $break, $result) . $break;
		else
			echo $result . $break;
	}

	$result = preg_quote($result);
	
	// Combine the tokenized text into one string
	// Add a space in front to prepare for the creation of the regexp pattern below
	$pattern = " " . trim($result);
	
	// This turns the link text into a regexp pattern that matches for a subtree
	$pattern = preg_replace("!(/)!", "\\/", $pattern);
	$pattern = preg_replace("/\s+(\S+)/", "(?:\\(\\S+\\s+)*\\1\\)+\\s*", $pattern);
	// If we're not in the punctuationPass, we want to match extra
	// punctuation before and after. If we're in the punctuationPass, there's
	// no need for that.
	if ($punctuationPass) {
		$pattern = preg_replace("/^(\\(\\?:\\\\\\(\\\\S\\+\\\\s\\+\\)\\*)/", "\\1(\\S*?)", $pattern);
		$pattern = preg_replace("/(\\\\\\)\\+\\\\s\\*)$/", "([^\\s()]*?)\\1", $pattern);
	} else {
		$pattern = preg_replace("/^(\\(\\?:\\\\\\(\\\\S\\+\\\\s\\+\\)\\*)/", "(?:\\(\\S+\\s+|\\((?:" . PUNCTUATION . ") [^\\s()]*?\\)\\s)*(\\S*?)", $pattern);
		$pattern = preg_replace("/(\\\\\\)\\+\\\\s\\*)$/", "([^\\s()]*?)\\)+\\s*(?:\\((?:" . PUNCTUATION . ") [^\\s()]*?\\)+)*\\s*", $pattern);
	}
	
	// Trim excess space and we have our regexp pattern.
	// Then store that into the cache.
	$getPatternCache[$pp][$link] = trim($pattern);
	
	return $getPatternCache[$pp][$link];
}

function isPossibleParse($text, $tree) {
	global $wordsCache, $break, $split;
	$words = $wordsCache[$text];
	if (!strlen($words))
		return false;
	$pattern = preg_replace("![^A-Za-z0-9]+!", ' ', $words);
	$pattern = preg_replace("!\s+!", "(.|\s)*?", trim(preg_quote($pattern)));
//	echo $words . $break;
//	echo $pattern . $break;
	return preg_match('!' . $pattern . '!m', $tree);
}

function prepHTMLForParser($text) {

	// Remove HTML entities and replace them with the corresponding symbols
	$text = html_entity_decode($text, ENT_NOQUOTES, "UTF-8");

	$text = preg_replace('!<br\s*/?>!i',"\n",$text);
	
	// Take out all the tags
	$text = stripTags($text);
	
	// Strip out all whitespace and replace with a single space
	$text = preg_replace("![ \t]+!m", " ", $text);
	// Replace all blocks of whitespace which include at least one \r or \n,
	// but can also include \s. Just \s is out, though.
	$text = preg_replace("!\s*([\r\n]+\s*)+!m", "\n", $text);
	$text = trim($text);

	return $text;
}

function doParse($tree, $text, $punctuationPass = false) {
	global $break, $split;
	$results = array();
	
	$text = prepHTMLForParser($text);

	if ($punctuationPass) {
		// Results passed in, this is a second pass, mark it
		$results["punctuation_pass"] = 1;
		// Remove all punctation nodes from the tree.
		$tree = preg_replace("/\\((" . PUNCTUATION . ") (" . PUNCTUATION . ")\\)/", '', $tree);
		// This may create blank lines. Kill them.
		$tree = preg_replace("/\r\s*\r/", "\r", $tree);
		// This may be extra space between nodes.
		$tree = preg_replace("/(\S|\\)) {2,}(\S|\\()/", '\1 \2', $tree);
		// There may also be enparentheses which are separated.
		$tree = preg_replace("/\\)\s+\\)/", '))', $tree);
	}
	
	// Turns the hyperlink text to a regexp pattern to match for a subtree.
	$pattern = getPattern($text, $punctuationPass);
	
	if (!$pattern) {
		// If we couldn't find a pattern, most likely that's because the parser
		// broke down. Kill the words parser so we don't affect subsequent
		// parses.
		closeParser('words');
		return array( "constituency" => "error", "error" => "missing_pattern" );
	}
	
	// Get the subtree that our hyperlink text was in, our hyperlink was regexp-ified
	$matches = array();
	if (!preg_match("/$pattern/", $tree, $matches)) {

		// Check to see if we even have the right tree or not!
		if (isPossibleParse($text, $tree))
			return array( "constituency" => "error", "error" => "missing_subtree",
			              "punctuation_pass" => isset($results["punctuation_pass"]) );

		return array( "constituency" => "error", "error" => "missing_parse",
									"punctuation_pass" => isset($results["punctuation_pass"]) );
	}

	// This is the subtree we just got, parens and all
	$subtree = trim($matches[0]);
	
//	var_dump($text, $pattern, $tree, $subtree);
	
	// Anything that was cut off when the author made the link (words with a link halfway in)
	$before = $matches[1];
	$after = $matches[2];
	
	// If there's anything before and after, the hyperlink cut off text, so we'll mark this
	if (strlen($before) || strlen($after))
		$results["almost"] = 1;
	
	// The subtree failed to get anything, or the tree didn't exist, these are errors
	if (!strlen($subtree)) {
		if (isset($results["punctuation_pass"]) && $results["punctuation_pass"]) {
			// The punctuation pass is likely to fail
			// we don't want it registered as an error,
			// make it a non-constituent and pass it back
			// where the non-constituent handling code 
			// will simply find missing nodes
			$results["constituency"] = "not_constituent";
		} else {
			// This is probably an error...bleh
			$results["constituency"] = "error";
			
			if (strstr($tree, "SENTENCE_SKIPPED_OR_UNPARSABLE") !== false)
				// MOST LIKELY, BUT BE WARNED, NOT ALWAYS TRUE
				$results["error"] = "missing_tree";
			else
				$results["error"] = "unknown_error";
		}
	} else {
		// Everything went okay, the subtree exists, let's do this
		
		// Parse for constituency on the subtree via terminal node delimiter count
		$results = parseConstituency($subtree, $results);
		
		// Retrieve the name of the immediately dominating node that dominates the entire link
		// (and other subtrees, if any)
		$results["immediate_node"] = getImmediatelyDominatingNode($subtree, $tree);
		
		if (($results["constituency"] == "not_constituent" || $results["constituency"] == "multiple_constituents") && !isset($results["failure_type"])) {
			// Any non-constituent that wasn't a cross-clausal one is handled here
			// Cross-clausal failures are the only ones with "failure_type" set in the results array

			// If a punctuation pass hasn't been done yet...
			if (!isset($results["punctuation_pass"])) {
				$firstPassResults = $results;
				$results = doParse($tree, $text, true);
				if ($results["constituency"] == 'error' &&
				  $firstPassResults["constituency"] != 'error') {
					$results = $firstPassResults;
					echo "reverting to first pass results!{$break}";
				}
			} else {
				// Punctuation pass didn't work or the link didn't need a punctuation pass anyways
				// figure out what happened, whether nodes were missing before or after
				$results = handleNonConstituent($tree, $subtree, $results);
			}
		}
	}
	
	return $results;
}

function handleNonConstituent($tree, $subtree, $results) {
	// Now we actually find out why these failed...
	$nLeftParens = substr_count($subtree, "(");
	$nRightParens = substr_count($subtree, ")");
	
	if ($nLeftParens >= $nRightParens) {
		// This is the position of the end of the subtree
		$iPos = strpos($tree, $subtree) + strlen($subtree);
		$appendage = substr($tree, $iPos);
		
		// Retrieve the node after the end of our subtree
		$match = array();
		preg_match("/\\((\\S*)/", $appendage, $match);
		
		// Set some flags
		$results["failure_type"] = "missing_after";
		$results["missing_node"] = $match[1];
	}
	
	if ($nLeftParens <= $nRightParens) {
		// This is the position of the beginning of the subtree
		$iPos = strpos($tree, $subtree);
		$appendage = substr($tree, 0, $iPos);
		
		// FIRST, try to see if there's a non-branching constituent right
		// at the end of the appendage.
		$match = array();
		if (preg_match("/\\((\\S*) \S*\\) ?$/", $appendage, $match))
			$results["missing_node"] = $match[1];
		else {		
			// SECOND, try the whitespace method:
			// Find the indentation level of the prefix:
			$match = array();
			preg_match("/([ \\t]*)$/", $appendage, $match);
			$indent = $match[1];
			
			// Find the last node which had that much indent:
			$match = array();
			preg_match_all("/^{$indent}\\((\\S*)/m", $appendage, $match);

			if (count($match[1]))
				$results["missing_node"] = $match[1][count($match[1]) - 1];
			else
				$results["missing_node"] = "ERROR";
		}

		// Set some flags
		if (isset($results["failure_type"])) {
			$results["failure_type"] = "missing_before_after";
			unset($results["missing_node"]);
		} else {
			$results["failure_type"] = "missing_before";
		}
	}
	
	return $results;
}

function countLeadingSpaces($text)
{
	$pos = 0;
	
	while (substr($text, $pos, 1) == " ")
	{
		$pos++;
	}
	
	return $pos;
}

function countLeadingTabs($text)
{
	$pos = 0;
	
	while (substr($text, $pos, 1) == "\t")
	{
		$pos++;
	}
	
	return $pos;
}

function stripEmptyProjection($tree) {
	$pattern = "/^\\(\\S+\\s*(?=\\()/";	
	if (preg_match($pattern, $tree))
		return trim(preg_replace($pattern, "", $tree));
	return false;
}

function stripEmptyProjections($tree)
{
	// This pattern only matches at the beginning
	// because otherwise, preg_replace would replace 
	// all empty projections inside the subtree as well
	// We only want empty projections dominating the subtree
	// to be taken out
	
	while ($newtree = stripEmptyProjection($tree)) {
		$tree = $newtree;
	}
	return $tree;
}

function stripEmptyEndParentheses($tree)
{
	// This pattern only matches at the end
	// because otherwise, preg_replace would replace 
	// all empty closing ")" inside the subtree as well
	// We only want empty ")" underneath the subtree
	// to be taken out
	$pattern = "/(?<=\\))\\)$/";
	
	while (preg_match($pattern, $tree))
	{
		$tree = trim(preg_replace($pattern, "", $tree));
	}
	
	return $tree;
}

function retabTree($tree, $tab = "\t")
{
	// Correctly tabs the tree, so that there is only one 
	// node per line (makes it easy to run the dominating node
	// algorithm)
	$tree = preg_replace('/\s+/', " ", $tree);
	$tree = str_replace(') )', "))", $tree);
	$tree = str_replace(') )', "))", $tree);

	$finalTree = "";
	$tabbing = "";
	$length = strlen($tree);
	$singleLineNode = false;
	
	for ($i = 0; $i < $length; $i++)
	{
		$char = substr($tree, $i, 1);
		
		if ($char == "(")
		{
			// Set this flag for ")" to properly tab for multiline nodes
			$singleLineNode = false;
			
			// New node, add a newline, add proper tabbing, and the char
			$finalTree .= "\n" . $tabbing . $char;
			
			// Increase the tabbing for subsequent nodes
			$tabbing .= $tab;
		}
		elseif ($char == ")")
		{
			// Decrease the tabbing if necessary
			if (strlen($tabbing) != 0)
			{
				$tabbing = substr($tabbing, 0, strlen($tabbing) - strlen($tab));
			}	
			
			if ($singleLineNode)
			{
				// End of singleline node, add the char
				$finalTree .= $char;
			}
			else
			{
				// End of multiline node, add a newline with the new tabbing
				$finalTree .= "\n" . $tabbing . $char;
			}
			
			// Set this flag for ")" to properly tab for multiline nodes
			$singleLineNode = false;
		}
		else
		{
			// Set this flag for ")" to properly tab for singleline nodes
			$singleLineNode = true;
			
			$finalTree .= $char;
		}
	}

	// make sure separate trees stay separate...
	$finalTree = preg_replace('/\)\h*(\r|\n|\r\n)\h*\(ROOT/', ")\n\n(ROOT", $finalTree);
	
	return $finalTree;
}

// The tree is formatted for our custom version of phpsyntaxtree
// with <<<LINK...LINK; annotations turned into red with (...), while
// matches based on the $additionalRegex marks things with underlining and [...]
function formatParseTree($tree, $additionalPattern = false, $returnFalseIfNoMatch = true) {
	global $formatMatches;

	if ( $additionalPattern ) {
		$matches = array();
		if ( !preg_match_all($additionalPattern, $tree, $matches) && $returnFalseIfNoMatch )
			return false;
		$matches = $matches[1]; // ignore the raw matches. We just want the content.

		// pass this to a global variable so we can pick it up in search_ajax...
		// pretty bad form, but...
		$formatMatches = $matches;
		foreach ($matches as $match) {
			// add a { to the left and a } to the right of the match
			$replacement = preg_replace('/^(.*?) ([^\(\)]*)\)/', '\1 {\2)', $match);
			$replacement = preg_replace('/((LINK;|[\)\s])+)$/', '}\1', $replacement);
			// wrap each leaf node with **...**
			$replacement = preg_replace('/ ([^\(\)]*)\)/', ' **\1**)', $replacement);
			$tree = str_replace($match, $replacement, $tree);
		}
	}

	$tree = str_replace('(', '[', $tree);
	$tree = str_replace(')', ']', $tree);
	$tree = preg_replace('/[\r\n]/', ' ', $tree);
	$tree = preg_replace('/\s+/', ' ', $tree);
	$matches = array();
	preg_match_all('/<<<LINK(.*?)LINK;/', $tree, $matches);
	$matches = $matches[1]; // ignore the raw matches. We just want the content.

	foreach ($matches as $match) {
		// add a ( to the left and a ) to the right of the link
		$replacement = preg_replace('/^(.*?) ([^\[\]]*)\]/', '\1 (\2]', $match);
		$replacement = preg_replace('/([\]\s]+)$/', ')\1', $replacement);
		// wrap each leaf node with *...*		
		$replacement = preg_replace('/ ([^\[\]]*)\]/', ' *\1*]', $replacement);
		$tree = str_replace("<<<LINK" . $match . "LINK;", $replacement, $tree);
	}

	// In this current configuration, it's possible to have text within a matched link which
	// ends up with *(**...**)*. Fix this here:
	$tree = str_replace('*(**', '***(', $tree);
	$tree = str_replace('**)*', ')***', $tree);

	return $tree;
}

function getImmediatelyDominatingNode($subtree, $tree)
{
	// Retrieves the name of the most immediate 
	// node that dominates the entire link
	
	// Strip down empty projections and end parens
	$subtree = stripEmptyProjections($subtree);
	$subtree = stripEmptyEndParentheses($subtree);
	
	// Retab the trees
	$subtree = trim(retabTree($subtree));
	$tree = trim(retabTree($tree));
	
	// Break the trees into lines
	$treeLines = explode("\n", $tree);
	$subtreeLines = explode("\n", $subtree);
	
	// Find the number of lines in the subtree
	$nSubtreeLines = sizeof($subtreeLines);
	
	if ($nSubtreeLines == 1)
	{
		// This is a single word node, just return that node name
		$match = array();
		preg_match("/\\((\\S*)/", $subtreeLines[0], $match);
		return $match[1];
	}
	else
	{
		// Get the first line of the subtree, we search down for
		// the least amount of spaces from here
		$firstSubtreeLine = $subtreeLines[0];
		
		// The following is essentially a indexOf() but it doesn't 
		// have to exactly match the array value, the value just needs to
		// contain the search item
		$nTreeLines = sizeof($treeLines);
		$hardContinue = false;
		for ($i = 0; $i < $nTreeLines; $i++)
		{
			// Don't check for != 0, 'cause 0 could mean it matches at index 0
			// Use a strict comparison with false
			if (strpos($treeLines[$i], $firstSubtreeLine) !== false)
			{
				// Double check using the other lines to make sure we have the 
				// right node by checking the lines that follow this one
				for ($j = 1; $j < $nSubtreeLines; $j++)
				{
					if (strpos($treeLines[$i + $j], $subtreeLines[$j]) === false)
					{
						// The next line didn't find a match,
						// continue to search in the main loop
						$hardContinue = true;
						break;
					}
				}
				
				if ($hardContinue)
				{
					// Part of the continue for the outer loop to continue searching
					$hardContinue = false;
					continue;
				}
				
				// We found the corresponding line in the subtree (hopefully,
				// it's mostly guaranteed if the subtree had a few or more lines, 
				// but if there were only 1 or 2 lines of common nodes, then we can't
				// really be sure.
				
				// Now, we search for the line in the subtree (in the actual tree, 'cause
				// it actually has all the tabs) that has the least amount of tabs
				
				// Set the first line as the barline for minimum number of tabs
				$minTabs = countLeadingTabs($treeLines[$i]);
				
				if ($minTabs == 0)
				{
					// This is the highest node, or a sibling to it, nothing contains this
					// return the node
					$match = array();
					preg_match("/\\((\\S*)/", $treeLines[$i], $match);
					return $match[1];
				}
				else
				{
					// Offset at 1, since we already did the first line
					$subtreeLimit = $i + $nSubtreeLines;
					for ($j = $i + 1; $j < $subtreeLimit; $j++)
					{
						$tabs = countLeadingTabs($treeLines[$j]);
						
						if ($tabs < $minTabs)
						{
							$minTabs = $tabs;
						}
					}
					
					// Now we count up to find a node with fewer tabs
					// i.e. a dominating node
					
					for ($j = $i - 1; $j > -1; $j--)
					{
						if (countLeadingTabs($treeLines[$j]) < $minTabs)
						{
							// This is our immediately dominating node
							$match = array();
							preg_match("/\\((\\S*)/", $treeLines[$j], $match);
							return $match[1];
						}
					}
					
					// If nothing...showed up...shoot...should've worked
					return "ERROR:inner";
				}
				
				// Would've returned by now
			}
			
			// Not yet...
		}
		
		// If we got here...uh-oh
		return "ERROR:outer";
	}
}

function parseConstituency($subtree, $results) {
	// Trim the subtree
	$subtree = trim($subtree);
	
	if (strpos($subtree, "\n\n") !== FALSE) {
		// The subtree has a full return, it is a cross-clausal link, i.e. not a constituent
		$results["constituency"] = "not_constituent";
		$results["failure_type"] = "x_clausal";
		
		return $results;
	}
	
	// Count the parens for balancing
	$nLeftParens = substr_count($subtree, "(");
	$nRightParens = substr_count($subtree, ")");
	
	if ($nLeftParens > $nRightParens)
	{
		// There are more left parens (open nodes) then there are right ones, we're missing some terminal nodes here...
		$dParens = $nLeftParens - $nRightParens;
		
		// Trim off nodes from the left so that it's now equal in paren numbers... why?
		$newSubtree = preg_replace("/^(\\(\\S+\\s+){" . strval($dParens) . "}/", "", $subtree);
		
		if ($newSubtree != $subtree)
		{
			// If we sucessfully made changes, then yay!
			$subtree = $newSubtree;
		}
		else
		{
			// Can't be done, not a constituent
			$results["constituency"] = "not_constituent";
			return $results;
		}
	}
	elseif ($nRightParens > $nLeftParens)
	{
		// Same deali-o
		
		$dParens = $nRightParens - $nLeftParens;
		
		$newSubtree = preg_replace("/\\){" . strval($dParens) . "}$/", "", $subtree);
		
		if ($newSubtree != $subtree)
		{
			$subtree = $newSubtree;
		}
		else
		{
			$results["constituency"] = "not_constituent";
			return $results;
		}
	}
	
	$zeroes = balanceAndCountZeroes($subtree);
	if ($zeroes === false) {
		$results["constituency"] = "not_constituent";
		return $results;
	}
	
	// Both of these values are okay. 
	if ($zeroes > 1)
		$results["constituency"] = "multiple_constituents";
	else
		$results["constituency"] = "constituent";
	return $results;
}

function balanceAndCountZeroes($tree) {
	// Match all parens, strip them all out for counting
	$parens = array();
	preg_match_all("/[()]/", $tree, $parens);
	
	// Helper variables for parens balancing
	$level = 0;
	$zeroes = 0;
	
	// We want to step through the process of balancing parentheses.
	// Along the way, we count the number of times the number of open parentheses
	// goes down to zero (stored in $zeroes). If $zeroes == 1, then we have one constituent
	// total. If $zeroes > 1, however, then this subtree was actually multiple constituents.
	foreach ($parens[0] as $paren) {
		if ($paren == "(")
			$level++;
		else
			$level--;
		
		if ($level == 0)
			$zeroes++; // We hit zero, so ++!
		
		if ($level < 0) {
			// Too many right parens, failed.
			return false;
		}
	}
	return $zeroes;
}

function stripTags($text) {
	// YEAH REGEXPS!
	return preg_replace("!(?:<(?:/?[^>]+)>)!m", "", $text);
}

function stringifyResults($results, $addDefaults = false) {
	$defaults = array('constituency'=>'NULL', 'failure_type'=>'NULL', 'almost'=>0, 'punctuation_pass'=>0, 'immediate_node'=>'NULL', 'missing_node'=>'NULL', 'error'=>'NULL');
	if ($addDefaults)
		$results = array_merge($defaults, $results);
	foreach ($results as $key => $value) {
		if ($value === 'NULL')
			$results[$key] = "$key = NULL";
		else if (is_string($value))
			$results[$key] = "$key = '$value'";
		else
			$results[$key] = "$key = $value";
	}
	
	return $updatedColumns = join(", ", $results);
}

$parser_processes = array();
$parser_pipes = array();
function parseStanford($text, $format = 'penn') {
	global $parser_processes, $parser_pipes, $log_handle, $break, $split;
	
	// cheat if it's just a single word...
	if (preg_match('/^[0-9a-zA-Z]+$/',$text)) {
		if ($format == 'words')
			return $text;
		if ($format == 'penn')
			return "(ROOT $text )";
	}
	
	if (!is_resource($log_handle))
		$log_handle = fopen("parser.log", "a");
	
	if (!isset($parser_processes[$format]) || !is_resource($parser_processes[$format]))
		openParser($format);
		
	// $parser_pipes[$format] now looks like this:
	// 0 => writeable handle connected to child stdin
	// 1 => readable handle connected to child stdout
	// Any error output will be appended to /tmp/error-output.txt
	
	fwrite($parser_pipes[$format][0], $text);
	fwrite($parser_pipes[$format][0], "\n\n"); // This acts as the EOF... (sort of)
	
	// Keep getting everything from STDOUT as long as STDERR has not printed the
	// "Parsing [sent. ??? len. 0]" message
	$results = array();
	$i = 0;
	while ($log = fgets($parser_pipes[$format][2])) {
		if(!defined('SUPPRESS_OUTPUT'))
			echo $log;
		fwrite($log_handle, $log);
		
		while ($line = fgets($parser_pipes[$format][1])) {
			if ($line == "SENTENCE_SKIPPED_OR_UNPARSABLE\n") {
				if ($i > DOT_TIMEOUT) {
					if(!defined('SUPPRESS_OUTPUT'))
						echo "got a SENTENCE_SKIPPED signal, probably from the last entry.{$break}";
					continue;
				} else {
					// This is a real sentence parsing error, so return it as is
					return $line;
				}
			}
			$results[] = rtrim($line);
			if (trim($line) == '')
				break;
		}
	
		if (strstr($log, "WARNING!! OUT OF MEMORY!") !== false) {
			if(!defined('SUPPRESS_OUTPUT'))
				echo "OUT OF MEMORY! Returning error{$break}";
			// kill off the current parser instance now, so that extra messages
			// don't spill over into the next parse.
			closeParser($format);
			return "SENTENCE_SKIPPED_OR_UNPARSABLE";
		}
	
		if (preg_match('/^Parsing \[sent. \d+ len. 0\]: \[\]/',$log)) {
			$i = 0;
			fwrite($log_handle, fgets($parser_pipes[$format][2]));
			while(fgets($parser_pipes[$format][1]) != "SENTENCE_SKIPPED_OR_UNPARSABLE\n") {
				$i++;
				if ($i > DOT_TIMEOUT)
					continue;
			}
			if(!defined('SUPPRESS_OUTPUT'))
				echo "($i dots){$break}";
			break;
		}
	}

	if ($format == 'penn') {
		$resultsString = join("\n", $results);

		// Remove line breaks and indentation, because we should be storing in a more pure format.
		return oneParsePerLine($resultsString);
	}
	if ($format == 'words')
		return join(" ", $results);
}

// Split tokenization off into a new function - really, they're separate things.
function tokenize($text) {
	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w") // stderr is a pipe that the child will write to
	);

	$process = proc_open('DYLD_LIBRARY_PATH=; java -cp ' . PARSER_DIR . '/stanford-parser.jar:lib/SimpleTokenizer:. SimpleTokenizer', $descriptorspec, $pipes);

	fwrite($pipes[0], $text);
	fclose($pipes[0]);
	$output = stream_get_contents($pipes[1]);

	fclose($pipes[1]);
	
	return $output;
}

function oneParsePerLine($tree) {
	// We need to run these queries twice because otherwise the matches overlap.
	// The first removes single line breaks, and the second squashes parentheses together.
	// The third and fourth remove spacing around LINKs.
	$result = preg_replace('/([^\r\n])\h*(\r|\n|\r\n)\h*([^\r\n])/', '$1 $3', $tree);
	$result = preg_replace('/([^\r\n])\h*(\r|\n|\r\n)\h*([^\r\n])/', '$1 $3', $result);
	$result = str_replace(") )", "))", $result);
	$result = str_replace(") )", "))", $result);
	$result = preg_replace('/\s*LINK;/', 'LINK;', $result);
	$result = preg_replace('/<<<LINK\s*/', '<<<LINK', $result);
	$result = preg_replace('/ +/', ' ', $result);
	return $result;
}

function openParser($format) {
	global $parser_processes, $parser_pipes, $log_handle, $web, $break;

	if(!defined('SUPPRESS_OUTPUT'))
		echo "creating new $format parser{$break}";
	$descriptorspec = array(
		 0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		 1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		 2 => array("pipe", "w") // stderr is a file to write to
	);
	$parser_pipes[$format] = array();
	
	$memory = '';
	$memory = " -Xmx1024M";
	if ($web)
		$memory = " -Xmx256M";
	$parser_processes[$format] = proc_open("DYLD_LIBRARY_PATH=; java $memory -cp " . PARSER_DIR . "/stanford-parser.jar edu.stanford.nlp.parser.lexparser.LexicalizedParser -outputFormat \"{$format}\" " . PARSER_DIR . "/englishPCFG.ser.gz -", $descriptorspec, $parser_pipes[$format]);
	stream_set_blocking($parser_pipes[$format][1], 0);
	
	if (!is_resource($parser_processes[$format]))
		die('Could not spawn process!');
}

function closeParser($format) {
	global $parser_processes, $parser_pipes, $getPatternCache;
	
	// clear cache!
	$getPatternCache = array('nopunc' => array(), 'greedy' => array());
	
	fclose($parser_pipes[$format][0]);
	fclose($parser_pipes[$format][1]);
	fclose($parser_pipes[$format][2]);

	// It is important that you close any pipes before calling
	// proc_close in order to avoid a deadlock
	return proc_close($parser_processes[$format]);
}

// Retrieves the corresponding tree from the database
function getTree($id) {
	// Ask for the tree
	$queryResult = mysql_query("SELECT stanford FROM " . ENTRIES_TABLE . " WHERE id = $id LIMIT 1");
	
	$entry = mysql_fetch_array($queryResult);
	$tree = $entry["stanford"];
	
	return $tree;
}

// Sets the tree
function setTree($id, $tree) {
	$tree = mysql_real_escape_string($tree);
	$user = mysql_real_escape_string(get_current_user());
	mysql_query("UPDATE " . ENTRIES_TABLE . " SET stanford = '$tree', modified_by = '$user' WHERE id = $id LIMIT 1");
}

// Finds the subtrees which correspond to the link text and adds the LINK
function addLINKs($tree, $text) {
	global $break;
	
	// Get the subtree that our hyperlink text was in, our hyperlink was regexp-ified
	$matches = array();
  
	$text = prepHTMLForParser($text);
	
	// Turns the hyperlink text to a regexp pattern to match for a subtree.
	$pattern = getPattern($text);

	// If we can't find the subtree...
	if (!preg_match("/$pattern/", $tree, $matches))
		return false;
	
	// This is the subtree
	$subtree = trim($matches[0]);
	
	// Do a replace with the link delimiters
	$words = explode(" ", preg_quote(trim(tokenize($text, "words"))));

	$matches = array();
	if (!preg_match("/^((.|\\n)*" . join("(.|\\n)*", $words) . ")/m", $subtree, $matches)) {
		echo "The words should be in here!\n";
		return false;
	} else {
		$subtreeBefore = trim($matches[1]);
	}

	$treeAfter = substr($tree, stripos($tree, $subtreeBefore) + strlen($subtreeBefore));
	$closingParenth = unclosedParensCount($subtreeBefore);

	$subtreeAfter_tagged = "";

	$stoppedAt = 0;

	for ($i = 0; $i < strlen($treeAfter); $i++) {
		if (!$closingParenth) { // included a ")" without its opening "(" within the link, stop here
			break;
		} else if ($treeAfter{$i} == "(") { // introduced new word, stop here
			break;
		} else if ($treeAfter{$i} == ")") { // included a valid ")", continue
			$closingParenth--;
			$subtreeAfter_tagged .= $treeAfter{$i};
		} else if (ctype_space($treeAfter{$i})) { // included a space, ignore
			continue;
		} else { // included a valid char, continue
			$subtreeAfter_tagged .= $treeAfter{$i};
		}
	}

	$subtree = $subtreeBefore . $subtreeAfter_tagged;

	$closingParenth = unclosedParensCount($subtree);
	// if there are extra unmatched (xx in the beginning of $subtree, let's get rid of them first.
	if ($closingParenth > 0) {
		for ($i = 0; $i < $closingParenth; $i++) {
			$newSubtree = stripEmptyProjection($subtree);
			if ($newSubtree === false)
				break;
			// if this is a valid subtree-prefix, i.e. at no point do we close off parentheses we didn't open
			if (balanceAndCountZeroes($newSubtree) !== false)
				$subtree = trim($newSubtree);
			else
				break; // else, stop now.
		}
	}

	// don't replace nothing.
	if (!strlen($subtree))
		return false;
	$newTree = str_replace_once($subtree, "<<<LINK" . $subtree . "LINK;", $tree);

	return $newTree;
}

// Determines the number of unclosed "(" parentheses in the given text.
function unclosedParensCount($text) {
	$unclosed = 0;
	$lines = explode("\n", $text);
	foreach ($lines as $line) {
		if (substr_count($line, "(") > substr_count($line, ")")) {
			$unclosed += substr_count($line, "(") - substr_count($line, ")");
		}
	}
	return $unclosed;
}

// http://www.sitepoint.com/forums/showthread.php?p=1392112
function str_replace_once($needle, $replace, $haystack){
	// Looks for the first occurence of $needle in $haystack
	// and replaces it with $replace.
	$pos = strpos($haystack, $needle);
	if ($pos === false) {
		// Nothing found
		return $haystack;
	}
	return substr_replace($haystack, $replace, $pos, strlen($needle));
}

function splitSentences($text) {
	// Manually fix ellipses with no spaces, as these are annoying.
	$text = preg_replace('/(\.{3,})([[:^space:]])/', '\1 \2', $text);
	// Manually fix stupid IE quotes.
	$text = str_replace('’', '\'', $text);
	// Manually fix single line breaks, as splitter ignores these.
	$text = preg_replace('/([[:^space:]])\n([[:^space:]])/', "\\1\n\n\\2", $text);

	// http://www.php.net/manual/en/function.proc-open.php
	$descriptorspec = array(
	  0 => array("pipe", "r"),	// stdin is a pipe that the child will read from
	  1 => array("pipe", "w"),	// stdout is a pipe that the child will write to
	  2 => array("pipe", "w") // stderr is a pipe that the child will write to
	);

	$process = proc_open("./tokenizer -S -E '' -L en-u8 -P -n", $descriptorspec, $pipes, dirname(__FILE__) . "/lib/tokenizer-1.0");

	$output = "";

	if(is_resource($process)) {
		fwrite($pipes[0], $text . "\n");
		fflush($pipes[0]);
		fclose($pipes[0]);

		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		fclose($pipes[2]);
		$returnStatus = proc_close($process);

		if($returnStatus != 0)
			return false;

		return trim($output);
	}
	else
		return false;
}

// Returns an array of command-line and GET arguments, parsed.
// Possible arguments which are omitted are false in the array.
// Return value is guaranteed to be sanitized.
// In other words, all keys will be keys to $definedArgs (minus the -).
function parseArgs($argv) {
	// It's possible that this would be better-implemented using iterators.
	global $definedArgs;
	
	// Set all defined arguments to be false by default.
	$result = array();
	$daKeys = array_keys($definedArgs);
	foreach($daKeys as $key) {
		$result[substr($key, 1)] = false;
	}
	
	if(isset($argv)) {
		// Make a [shallow] copy of argv
		$tempArgv = array();
		foreach($argv as $argi) {
			array_push($tempArgv, $argi);
		}

		// Go through the arguments sequentially, removing those that correspond to arguments and their inputs.
		while(count($tempArgv) > 0) {
			$arg = array_shift($tempArgv);
			if(isset($definedArgs[$arg]))
			{
				$argName = substr($arg, 1);
				$numInp = $definedArgs[$arg];

				if($numInp == 0)
					$result[$argName] = true;
				else if($numInp == 1)
					$result[$argName] = array_shift($tempArgv);
				else {
					$inpList = array();
					for($i = 0; $i < $numInp; $i++) {
						array_push($inpList, array_shift($tempArgv));
					}
					$result[$argName] = $inpList;
				}
			}
		}
	}

	// Now we go through GET and environment variables if they're set.
	// Behavior if only one of start, end is set is to range over all. Maybe we should change this?
	if (isset($_GET['start']) && isset($_GET['end']))
		$result['range'] = array($_GET['start'], $_GET['end']);

	if (getenv('CONSTITUENCY_START') !== false && getenv('CONSTITUENCY_END') !== false)
		$result['range'] = array(getenv('CONSTITUENCY_START'), getenv('CONSTITUENCY_END'));

	foreach($daKeys as $key) {
		$name = substr($key, 1);
		if (isset($_GET[$name]))
			$result[$name] = $_GET[$name];
		$envName = 'CONSTITUENCY_' . strtoupper($name);
		if (getenv($envName) && !$result[$name])
			$result[$name] = getenv($envName);
	}
	if ($result['entry'])
		$result['range'] = array($result['entry'], $result['entry']);

	// if range not set by now, default to "everything"
	if (!$result['range'])
		$result['range'] = array(0,100000000);

	return $result;
}

// Add a tag to the cross-reference table.
function addTag($entry, $id, $tid, $type = 'human', $user = USERNAME) {
	global $db;
	$db->insert(TAGS_TABLE, array('tid' => $tid, 'entry' => $entry, 'lid' => $id, 'type' => $type, 'user' => $user), array('%d', '%d', '%d', '%s', '%s'));
}

// Remove a tag from the cross-reference table.
function delTag($entry, $id, $tid) {
	global $db;
	$db->query("delete from " . TAGS_TABLE . " where tid = $tid and entry = $entry and lid = $id");
}
