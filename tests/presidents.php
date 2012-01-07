<?php

/**
 * UNIT TEST: presidents (entry 87943)
 */

$tree = <<<TREE
(ROOT
  (S
    (NP
      (NP
        (NP (NNP Jerry) (POS 's))
        (NN Ranking))
      (PP (IN of)
        (NP (DT the) (NNP U.S.) (NNPS Presidents))))
    (: -)
    (VP (VBP Think)
      (ADVP (RB fast)))))
TREE;

$text = "Jerry's Ranking of the U.S. Presidents";
$results = doParse($tree, $text);
test_assert($results["constituency"] === "constituent", "Should be an NP.");
test_assert($results["immediate_node"] === "NP", "Should be an NP.");
