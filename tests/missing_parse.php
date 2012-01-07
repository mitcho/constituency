<?php

// Checks to make sure "missing_parse" is returned correctly.

$results = doParse('(ROOT (NP (N test)))', 'test');
test_assert($results['constituency'] != 'error', "'test' shouldn't yield an error");

$results = doParse('(ROOT (NP (N test )))', 'text');
test_assert($results['constituency'] == 'error', "'text' should be an error");
test_assert($results['error'] == 'missing_parse', "the error should be missing_parse");
