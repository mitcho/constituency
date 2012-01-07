<?php

// Parallelizes parse_entries. Script requires PHP extensions pcntl and ssh2.
// Behavior is to have {$count} processes on each machine.
// Looks for a public/private key pair in current working directory, named "constituency_rsa" and "constituency_rsa.pub".

// Machines, username, remote path to constituency dir, and location of private key file (public is $identity . ".pub")
// File should have $machines array, $username, $remotePath, $identity
include("parallelize_config.php");

if($argc < 5)
	die("Usage: php parallelize.php start end count parse_entries|judge_constituency [args]\n" . 
	    "Creates count copies of parse_entries or judge_constituency on each target machine, dividing up the entries evenly.");

$start = intval($argv[1]);
$end = intval($argv[2]);
$count = intval($argv[3]);

$newArgv = array_slice($argv, 5);

$numMachines = count($machines);
$perProc = ($end - $start + 1) / ($count * $numMachines);
$i = 0;
$j = 0;

// fork a whole bunch of times
// $j is machine index, $i is process index
while($i < $count - 1 && pcntl_fork() > 0)
	$i++;

while($j < $numMachines - 1 && pcntl_fork() > 0)
	$j++;

$processIdx = $j * $count + $i;
$newStart = intval($perProc * $processIdx + $start);
$newEnd = intval($perProc * ($processIdx + 1) + $start - 1);

$machine = $machines[$j];
$connection = ssh2_connect($machine);
if(!$connection)
	die("Couldn't connect to machine.\n");

// We use public key authentication because many machines don't have password authentication activated.
// Keyboard-interactive is not password.
if(!ssh2_auth_pubkey_file($connection, $username, $identity . ".pub", $identity))
	die("Couldn't authenticate.\n");

if($_ENV['CONSTITUENCY_TABLES'])
	$extra = "export CONSTITUENCY_TABLES='{$_ENV['CONSTITUENCY_TABLES']}' && ";
else
	$extra = "";

$stream = ssh2_exec($connection, $extra . "cd $remotePath && 2>&1 php {$argv[4]}.php -range $newStart $newEnd " . implode(" ", $newArgv));

if(!file_exists("logs/"))
	mkdir("logs");
$logFile = fopen("logs/$machine-$i.txt", "w");
if(!$logFile)
	die("Couldn't open file for logging.");

// copy stream data repeatedly, without taking up all the processing power
while(!feof($stream)) {
	$bytes = fread($stream, 65536);
	fwrite(STDOUT, $bytes);
	fwrite($logFile, $bytes);
	usleep(200000);
}

echo(stream_get_contents($stream));
fclose($stream);
fclose($logFile);

?>
