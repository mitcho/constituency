<?php

/**
 * UNIT TEST: media (entry 7672)
 */

// NOTE: this tree has been modified!
$tree = <<<TREE
(ROOT
  (SQ
    (SQ
			(VP (VB Right)
				(NP
					(NP (NNP Wing\/Left) (NNP Wing\/Corporate) (NNP Media) (. ?)))))
      (SQ (VBP Are)
        (NP (DT the) (NNS biases)))
      (FRAG
        (PP (RB just) (IN in)
          (NP
            (NP (DT the) (NN eye))
            (PP (IN of)
              (NP (DT the) (NN beholder)))))))))
TREE;

$text = "Right Wing/Left Wing/Corporate Media?";
$results = doParse($tree, $text);
test_assert($results["constituency"] === "constituent", "Right Wing/Left Wing/Corporate Media? should be a constituent");
test_assert($results["immediate_node"] === "VP", "Right Wing/Left Wing/Corporate Media? should be a constituent");