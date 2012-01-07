<?php

/**
 * UNIT TEST: orca (entry 92933)
 */

$text = <<<HTML
When he's not busy on such excursions, bunnie writes about <a href="http://www.bunniestudios.com/blog/?cat=2">hacking</a> (and more specifically, <a href="http://www.bunniestudios.com/blog/?cat=6">Chumby hacking</a>).
			<br>More bunnie goodness:<br />
 <br />
* While at MIT, Andrew was part of <a href="http://web.mit.edu/orca/www/2000_comp1998.shtml">the first Project ORCA team</a> at the first annual International Autonomous Underwater Vehicle Competition, where <a href="http://tech.mit.edu/V118/N44/underwater.44n.html">they won first place</a>, in part because the other tree teams didn't complete the course.<br />
HTML;

$text = prepHTMLForParser($text);
$stanfordTree = parseStanford($text, 'penn');

test_assert(strstr($stanfordTree, "Chumby") !== false, "Chumby is in the parse");
test_assert(strstr($stanfordTree, "ORCA") !== false, "Chumby is in the parse");
