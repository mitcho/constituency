<?php

include_once('reports_functions.php');

searchAndRecordByCapturedWord('( <NODE> (CC {WORD} ) NODE )', 'left conj');
searchAndRecordByCapturedWord('( NODE (CC {WORD} ) <NODE> )', 'right conj');

searchAndRecordByCapturedWord('( <NODE (CC {WORD} )> NODE )', 'left with conj');
searchAndRecordByCapturedWord('( NODE <(CC {WORD} ) NODE> )', 'right with conj');

printResults();
