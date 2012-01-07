<?php

include_once('reports_functions.php');

//searchAndRecordByCapturedWord('(VP {<NODE>} (NP NODE+ ) (NP NODE+ ) )', 'np-np: link on verb');
searchAndRecordByCapturedWord('(VP <{NODE} (NP NODE+ )> (NP NODE+ ) )', 'np-np: link on V-IO', true);
searchAndRecordByCapturedWord('(VP {NODE} <(NP NODE+ ) (NP NODE+ )> )', 'np-np: link on objects', true);
//searchAndRecordByCapturedWord('<(VP {NODE} (NP NODE+ ) (NP NODE+ ) )>', 'np-np: link on VP');

//searchAndRecordByCapturedWord('(VP {<NODE>} (NP NODE+ ) (PP {NODE} NP ) )', 'np-pp: link on verb');
searchAndRecordByCapturedWord('(VP <{NODE} (NP NODE+ )> (PP {NODE} (NP NODE+ ) ) )', 'np-pp: link on V-DO', true);
searchAndRecordByCapturedWord('(VP {NODE} <(NP NODE+ ) (PP {NODE} (NP NODE+ ) )> )', 'np-pp: link on objects', true);
//searchAndRecordByCapturedWord('<(VP {NODE} (NP NODE+ ) (PP {NODE} NP ) )>', 'np-pp: link on VP');

//var_dump($allWords);

printResults();
