<?php

include_once('reports_functions.php');

$leftPart = searchAndReturnBackrefs('(NP <{NODE+}> {NODE+} )');
var_dump($leftPart);

$leftSansDT = searchAndReturnBackrefs('(NP (DT WORD ) <{NODE+}> {NODE+} )');
var_dump($leftSansDT);

$leftSansDTMultiLevel = searchAndReturnBackrefs('(NP (NP (DT WORD ) <{NODE+}> ) {NODE+} )');
var_dump($leftSansDTMultiLevel);
