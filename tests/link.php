<?php

// This file tests the placement of <<<LINK...LINK; tags.

// Entry 97
// Thanks to Amazon, we can now see what everyone who works for Microsoft likes to read.

// the tree of the link sentence
$tree = <<<TREE
(ROOT
  (S
    (S
      (VP (VBG Thanks)
        (PP (TO to)
          (NP (NNP Amazon)))))
    (, ,)
    (NP (PRP we))
    (VP (MD can)
      (ADVP (RB now))
      (VP (VB see)
        (NP
          (NP (WP what) (NN everyone))
          (SBAR
            (WHNP (WP who))
            (S
              (VP (VBZ works)
                (SBAR (IN for)
                  (S
                    (NP (NNP Microsoft))
                    (VP (VBZ likes)
                      (S
                        (VP (TO to)
                          (VP (VB read)))))))))))))
    (. .)))
TREE;

$result0 = addLINKs($tree, "can now see what everyone who works for Microsoft likes to read");
test_assert(preg_match("!<<<LINK\(VP \(MD !m", $result0), "The <<<LINK should be added before the VP");
test_assert(stristr($result0, "read)))))))))))))LINK;"), "The LINK; should be added after all the ) in the line, which are all part of the VP.");

$result1 = addLINKs($tree, "what everyone who works for Microsoft likes to read");
test_assert(preg_match("!<<<LINK\(NP\s*\(NP!m", $result1), "The <<<LINK should be added before two (NP's");
test_assert(stristr($result1, "read)))))))))))LINK;"), "The LINK; should be added after we capture as many ) as possible while staying in the link.");

$result2 = addLINKs($tree, "what everyone who works for Microsoft");
echo $result2 . "\n";
test_assert(preg_match("!<<<LINK\(NP \(WP!m", $result2), "The <<<LINK should be added before just one (NP");
test_assert(stristr($result2, "Microsoft))LINK;"), "If we end after Microsoft, stop before we add new words, like 'likes'");

$result3 = addLINKs($tree, "to Amazon, we");
echo $result3 . "\n";
test_assert(stristr($result3, "<<<LINK(PP (TO"), "The <<<LINK should be before (PP");
test_assert(stristr($result3, "we))LINK;"), "Capture as many ) we can.");

$result4 = addLINKs($tree, "everyone who works for Microsoft");
test_assert(stristr($result4, "what) <<<LINK(NN"), "The <<<LINK should be added in the middle of the line, before (NN");
test_assert(stristr($result4, "Microsoft))LINK;"), "If we end after Microsoft, stop before we add new words, like 'likes'");


//	test_assert($test['result'] == $updatedColumns, "Link " . strval($index) . " should be: " . $test['result'] . "\ngot instead: {$updatedColumns}");


// entry 19
$anothertree = <<<TREE
(ROOT
  (S
    (S
      (NP (NNP Cat-Scan) (. .) (NNP com))
      (VP (VBZ is)
        (NP
          (NP (CD one))
          (PP (IN of)
            (NP
              (NP (DT the) (JJ strangest) (NNS sites))
              (SBAR
                (S
                  (NP (PRP I))
                  (VP (VBP 've)
                    (VP (VBN seen)
                      (PP (IN in)
                        (NP (DT some) (NN time)))))))))))
      (. .))
    (NP (PRP I))
    (VP (VBP have)
      (NP (DT no) (NN idea))
      (SBAR
        (SBAR
          (WHADVP (WRB how))
          (S
            (NP (DT these) (NNS people))
            (VP (VBD got)
              (SBAR
                (S
                  (NP (PRP$ their) (NNS cats))
                  (VP (VBD wedged)
                    (PP (IN into)
                      (NP (PRP$ their) (NNS scanners)))))))))
        (, ,)
        (CC or)
        (FRAG
          (WHADVP (WRB why)))))
    (. .)))
TREE;

$result5 = addLINKs($anothertree, "Cat-Scan.com");
test_assert(stristr($result5, "<<<LINK(NP (NNP Cat-Scan) (. .) (NNP com))LINK;"), "The LINK; is just the NP.");

// entry 343
$yetanothertree = <<<TREE
(ROOT
  (S
    (VP (VB Ugh))
    (. !)))

(ROOT
  (S
    (S
      (NP (NNP Jakob) (NNP Nielsen))
      (VP (VBZ is)
        (PP (IN at)
          (NP (PRP it)))
        (ADVP (RB again))))
    (, ,)
    (NP (DT this) (NN time))
    (VP (VBD quantifying)
      (NP (NN design) (NNS conventions))
      (PP (IN for)
        (NP (DT the) (NN web))))
    (. .)))
TREE;

$result6 = addLINKs($yetanothertree, "Ugh! Jakob Nielsen is at it again,");
test_assert(stristr($result6, "<<<LINK(ROOT"), "The LINK; starts with the ROOT.");
test_assert(stristr($result6, ",)LINK;"), "The LINK; ends with the comma.");
