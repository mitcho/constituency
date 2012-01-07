<?php

/**
  Unit test for embedded dropped DT, #281
*/

$tree = <<<TREE
(ROOT (S (PP (IN Inside) (NP (PRP$ its) (NN sound))) (NP (NNS archives)) (VP (VBP are) (NP (NP (JJ Real) (NNP Audio) (NNS recordings)) (PP (IN of) (NP (NP (DT some)) (PP (IN of) (NP (NP (DT the) (NNS highlighs)) (PP (IN in) (NP (NN radio) (NN history))))) (, ,) (PP (VBG including) (NP (NP (NP (DT the) <<<LINK(NNP Abdication)) (PP (IN of) (NP (NNP Edward) (CD VIII))LINK;)) (CC and) <<<LINK(NP (NP (NP (NNP Hitler) (POS 's)) (NNP Speech)) (PP (IN after) (NP (NP (DT the) (NNP Annexation)) (PP (IN of) (NP (NNP Poland))))))LINK;)))))) (. .)))
TREE;

$query = '(NP (NP (D WORD ) <{NODE+} ) {NODE+}> )';

$pat = new Pattern($query, $tree);
//var_dump($pat->regexp);
$match = $pat->matchesBalance($tree);
test_assert($match, "Abdication failed to match $query");
