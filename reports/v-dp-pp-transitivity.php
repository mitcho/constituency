<?php

include_once('reports_functions.php');

searchAndRecordByCapturedWord('(VP <(V {WORD} ) (NP NODE+ )> (PP NODE+ ) )', 'link on VO');
searchAndRecordByCapturedWord('<(VP (V {WORD} ) (NP NODE+ ) (PP NODE+ ) )>', 'link on VP');

printHeader();
printResults();
