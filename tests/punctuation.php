<?php

/**
 * UNIT TEST: punctuation (entry 180)
 */

$tree1 = <<<TREE
(ROOT
  (NP (NNP Dismember) (NNP Me) (NNP Elmo) (. .)))
TREE;

$tree2 = <<<TREE
(ROOT
  (S
    (S
      (S
        (SBAR (IN If)
          (S
            (NP (PRP you))
            (VP (VBP 're)
              (ADJP (JJ interested)
                (PP (IN in)
                  (NP (DT the) (NNP Mozilla) (NN article)))))))
        (, ,)
        (NP (EX there))
        (VP (VBZ 's)
          (NP (DT a) (NN mirror))
          (ADVP (RB here))))
      (, ,)
      (CC and)
      (S
        (PP (IN in)
          (NP (NN case)))
        (NP (PRP you))
        (VP (MD ca) (RB n't)
          (VP (VB get)
            (PP (TO to)
              (NP
                (NP (PRP$ my) (NN story))
                (PP (IN on)
                  (NP (NN evolt)))))))))
    (, ,)
    (NP (PRP I))
    (VP (VBD put)
      (NP (DT a) (NN mirror))
      (ADVP (RB here)))
    (. .)))

TREE;

$text = "Dismember Me Elmo";

$results = doParse($tree1, $text);
test_assert($results["constituency"] === "constituent", "Dismember Me Elmo is a constituent, modulo punctuation");
test_assert($results["immediate_node"] === "NP", "Immediate node is a NP");

$text = "there's a mirror here";

$results = doParse($tree2, $text);
test_assert($results["constituency"] === "multiple_constituents", "The mirror sentence is a multi-constituent");
test_assert($results["failure_type"] === "missing_before", "Failure is missing_before");
test_assert($results["missing_node"] === "SBAR", "But the missing_node is an SBAR");
