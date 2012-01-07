<?php

include_once('reports_functions.php');

searchAndRecordByCapturedWord('(VP {<NODE>} (NP NODE+ ) )', 'link on verb', true);
//searchAndRecordByCapturedWord('(VP {NODE} (NP NODE+ ) )', 'no link specified', true);
//searchAndRecordByCapturedWord('(VP {NODE} <(NP NODE+ )> )', 'link on object', true);
searchAndRecordByCapturedWord('<(VP {NODE} (NP NODE+ ) )>', 'link on VP', true);

printResults();
