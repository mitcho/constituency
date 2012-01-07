<?php

include_once('reports_functions.php');

searchAndRecordByCapturedWord('(NP (JJ WORD ) <(JJ WORD ) NODE> )', 'exclude left adj', true);

printResults();
