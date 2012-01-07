<?php

/**
 * UNIT TEST: dominating node test
 */

$tree = <<<TREE
(XP
	(NP Contemporary)
	(NP (NN news) (NNS stories)))
TREE;

$text = "Contemporary news";
$results = doParse($tree, $text);
test_assert($results["constituency"] === "not_constituent", "Contemporary news should not be a constituent.");
test_assert($results["immediate_node"] === "XP", "The dominating node should be XP.");