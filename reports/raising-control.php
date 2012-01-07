<?php

include_once('reports_functions.php');

searchAndRecordByCapturedWord('(VP {NODE} (S (NP NODE+) (VP (TO to ) NODE+ ) ) )', 'baseline');
searchAndRecordByCapturedWord('(VP <{NODE} (S (NP NODE+)> (VP (TO to ) NODE+ ) ) )', 'link on V-subj');
searchAndRecordByCapturedWord('(VP <{NODE} (S (NP NODE+) (VP (TO to )> NODE+ ) ) )', 'link on V-subj-to');
searchAndRecordByCapturedWord('(VP {NODE} <(S (NP NODE+) (VP (TO to ) NODE+ ) )> )', 'link on subj');
searchAndRecordByCapturedWord('(VP {NODE} (S (NP NODE+) <(VP (TO to ) NODE+ )> ) )', 'link on embedded VP');

//var_dump($allWords);

printResults();
