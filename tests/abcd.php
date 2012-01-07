<?php

// This file tests the alpha-beta-gamma(-delta) cases

$tests = array(
	array( "text" => "alpha beta", "tree" => 0, "result" =>
		"constituency = 'constituent', immediate_node = 'Y'" ),
	array( "text" => "beta gamma", "tree" => 0, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_before_after', immediate_node = 'X', punctuation_pass = 1" ),
	array( "text" => "gamma delta", "tree" => 0, "result" =>
		"constituency = 'constituent', immediate_node = 'Z'" ),
	
	array( "text" => "alpha beta", "tree" => 1, "result" =>
		"constituency = 'constituent', immediate_node = 'Y'" ),
	array( "text" => "beta gamma", "tree" => 1, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_before', immediate_node = 'X', missing_node = 'A', punctuation_pass = 1" ),
	
	array( "text" => "alpha beta", "tree" => 2, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_after', immediate_node = 'X', missing_node = 'C', punctuation_pass = 1" ),
	array( "text" => "beta gamma", "tree" => 2, "result" =>
		"constituency = 'constituent', immediate_node = 'Z'" ),
	
	array( "text" => "alpha beta", "tree" => 3, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_after', immediate_node = 'Y', missing_node = 'C', punctuation_pass = 1" ),
	array( "text" => "beta gamma", "tree" => 3, "result" =>
		"constituency = 'constituent', immediate_node = 'U'" ),
	array( "text" => "gamma delta", "tree" => 3, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_before', immediate_node = 'X', missing_node = 'B', punctuation_pass = 1" ),
	
	array( "text" => "alpha beta", "tree" => 4, "result" =>
		"constituency = 'constituent', immediate_node = 'U'" ),
	array( "text" => "beta gamma", "tree" => 4, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_before', immediate_node = 'Y', missing_node = 'A', punctuation_pass = 1" ),
	array( "text" => "gamma delta", "tree" => 4, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_before', immediate_node = 'X', missing_node = 'U', punctuation_pass = 1" ),
	
	array( "text" => "alpha beta", "tree" => 5, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_after', immediate_node = 'X', missing_node = 'U', punctuation_pass = 1" ),
	array( "text" => "beta gamma", "tree" => 5, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_after', immediate_node = 'Z', missing_node = 'D', punctuation_pass = 1" ),
	array( "text" => "gamma delta", "tree" => 5, "result" =>
		"constituency = 'constituent', immediate_node = 'U'" ),
	
	array( "text" => "alpha beta", "tree" => 6, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_after', immediate_node = 'X', missing_node = 'C', punctuation_pass = 1" ),
	array( "text" => "beta gamma", "tree" => 6, "result" =>
		"constituency = 'constituent', immediate_node = 'U'" ),
	array( "text" => "gamma delta", "tree" => 6, "result" =>
		"constituency = 'not_constituent', failure_type = 'missing_before', immediate_node = 'Z', missing_node = 'B', punctuation_pass = 1" )
);

$trees = array();
$trees[0] = <<<TREE
(X
  (Y
    (A alpha)
    (B beta))
  (Z
    (C gamma)
    (D delta)))
TREE;

$trees[1] = <<<TREE
(X
  (Y
    (A alpha)
    (B beta))
  (Z
    (C gamma)))
TREE;

$trees[2] = <<<TREE
(X
  (Y
    (A alpha))
  (Z
    (B beta)
    (C gamma)))
TREE;

$trees[3] = <<<TREE
(X
  (Y
    (A alpha)
    (U
      (B beta)
      (C gamma)))
  (Z (D delta)))
TREE;

$trees[4] = <<<TREE
(X
  (Y
    (U
      (A alpha)
      (B beta))
    (C gamma))
  (Z (D delta)))
TREE;

$trees[5] = <<<TREE
(X
  (Y (A alpha))
  (Z
    (B beta)
    (U
      (C gamma)
      (D delta))))
TREE;

$trees[6] = <<<TREE
(X
  (Y (A alpha))
  (Z
    (U
      (B beta)
      (C gamma))
    (D delta)))
TREE;

foreach ($tests as $index => $test) {
	
	$text = $test['text'];
	$tree = $trees[$test['tree']];
	
//	echo "$text\n";
//	echo "$tree\n";
	
	// Parse the tree with the text and determine the constituency
	$results = doParse($tree, $text);
	
	ksort($results);
	$updatedColumns = stringifyResults($results);
	
//	echo "\t$updatedColumns\n";
	
	test_assert($test['result'] == $updatedColumns, "Link " . strval($index) . " should be: " . $test['result'] . "\ngot instead: {$updatedColumns}");
}