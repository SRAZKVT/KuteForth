#!/usr/bin/php
<?php
	if (count($argv) < 2) {
		usage($argv[0]);
		exit(1);
	}
	$filepath;
	$autorun = $argv[1] == "-r";
	if ($autorun) {
		$filepath = $argv[2];
	} else {
		$filepath = $argv[1];
	}
	if (!endsWith($filepath, ".kf")) {
		usage($argv[0]);
		exit(1);
	}
	$words = getWords($filepath);

	foreach ($words as $w) {
		echo "|" . $w . "|\n";
	}

	
	exit(69);

	function getWords($filepath) {
		$ret = array();
		$file = fopen($filepath, "r") or die ("ERROR: Unable to open the file " . $filepath);
		$code = fread($file, filesize($filepath));
		$lines = explode("\n", $code);
		foreach ($lines as $line) {
			$words = explode(" ", $line);
			foreach ($words as $word) {
				$word = trim($word);
				array_push($ret, $word);
			}
		}
		fclose($file);
		return $ret;
	}

	function endsWith($str, $end) {
		$end_len = strlen($end);
		return $end_len > 0 ? substr($str, -$end_len) === $end: true;
	}

	function usage($invoke) {
		echo "Usage :: " . $invoke . " [-r] <program_name>.kf\n";
	}
?>
