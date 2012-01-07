<?php

/**
   Unit testing the search functionality.
*/

$catscan = <<<TREE
(ROOT
  (S
    <<<LINK(NP (NNP Cat-Scan) (. .) (NNP com))
LINK;
    (VP (VBZ is)
      (NP
        (NP (CD one))
        (PP (IN of)
          (NP
            (NP (DT the) (JJ strangest) (NNS sites))
            (SBAR
              (S
                (NP (PRP I))
                (VP (VBP ve)
                  (VP (VBN seen)
                    (PP (IN in)
                      (NP (DT some) (NN time)))))))))))
    (. .)))

(ROOT
  (S
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
$successes = array("success" => array("(NP (DT these) NODE )", "(VBD wedged) (PP NODE NODE", "(NNP com)) ("),
		   "fail" => array("(NP (DT an) (NN idea)", "(NNS scanners) NODE"));
foreach ($successes as $type => $queries) {
	foreach ($queries as $query) {
		$pat = new Pattern($query, $catscan);
		$r = $pat->regexp;
		$phpr = $pat->phpregexp;
		$match = preg_match("/$r/ms", $catscan) && preg_match("/$phpr/ms", $catscan);
		if ($type == "success")
			test_assert($match, "Cat-scan failed to match $query");
		else
			test_assert(!$match, "Cat-scan shouldn't have matched $query");
	}
}
