<?php

// word_lengths.out is the result of this query:
// select (length(text) - length(replace(trim(text), ' ', ''))) + 1 as words, count(id) from links group by words
$word_lengths_txt = file_get_contents('word_lengths.out');

$word_length_counts = array();
$total_count = 0;
foreach ( preg_split('/[\r\n]/', $word_lengths_txt) as $line ) {
	$linedata = explode("\t", $line);
	if ( !is_array($linedata) || empty($linedata) )
		continue;
	$word_length_counts[(int) $linedata[0]] = (int) $linedata[1];
	$total_count += (int) $linedata[1];
}

$word_length_distribution = array_map(
	function($x) {
		global $total_count;
		return $x / $total_count;
	}, $word_length_counts);

function C($x = 0) {
	return(fact($x*2) / (fact($x) * fact($x+1)));
	// this is based on the following definition: (2n)!/(n!(n+1)!)
}

function fact($int){
		if($int<2)return 1;
		for($f=2;$int-1>1;$f*=$int--);
		return $f;
}

$sum = 0;
foreach ($word_length_distribution as $len => $dist) {
	if ($len <= 15) {
//		echo "P({$len}-word constituent in 15-word sentence) = " . (C(15 - $len) / C(15 - 1)) . "\n";
		$sum += $dist * (C(15 - $len) / C(15 - 1));
	} else {
		$sum += $dist;
	}
}

echo "EX = " . $sum . "\n";

/*
	CatalanNumber(1000) will produce the following number:

2046105521468021692642519982997827217179245642339057975844538099572176010191891863964968026156453752
4490157505694285950973181636343701546373806668828863752033596532433909297174310804435090075047729129
7314225320935212694683984479674769763853760010063791881932656973098208302153805708771117628577790927
5869648636874856805956580057673173655666887003493944650164153396910927037406301799052584663611016897
2728933055321162921432710371407187516258398120726824643431537929562817485824357514814985980875869986
03921577523657477775758899987954012641033870640665444651660246024318184109046864244732001962029120

	.. which is the 1000th valid Catalan number.
*/
