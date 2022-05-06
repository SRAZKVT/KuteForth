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
	$tokens = getTokens($filepath);


	foreach ($tokens as $t) {
		if ($t->word == "") continue;
		
		echo $t->getTokenInformation() . "\n";
	}

	exit(69);

	function getTokens($filepath) {
		$ret = array();
		$file = fopen($filepath, "r") or die ("ERROR: Unable to open the file " . $filepath);
		$code = fread($file, filesize($filepath));
		$lines = explode("\n", $code);
		$line_place = 0;
		foreach ($lines as $line) {
			$line_place++;
			$char_place = 0;
			$chars = str_split($line);
			$current_word = "";
			foreach ($chars as $c) {
				$char_place++;
				if (!isWhitespace($c)) {
					$current_word = $current_word . $c;
				} else {
					if ($current_word !== "") {
						$token = new Token($current_word, $line_place, $char_place - strlen($current_word), $filepath);
						array_push($ret, $token);
					}
					$current_word = "";
				}
			}
			$char_place++;
			$token = new Token($current_word, $line_place, $char_place - strlen($current_word), $filepath);
			array_push($ret, $token);
									
		}
		fclose($file);
		return $ret;
	}

	class Token {
		public $word;
		public $row;
		public $col;
		public $filename;

		function __construct($word, $row, $col, $filename) {
			$this->word = $word;
			$this->row = $row;
			$this->col = $col;
			$this->filename = $filename;
		}

		function getTokenInformation() {
			return $this->filename . ":" . $this->row . ":" . $this->col . " [" . $this->word . "]";
		}
	}

	function isWhitespace($c) {
		return ($c === ' ' || $c === "\n" || $c === "\t" || $c === '\r');
	}

	function endsWith($str, $end) {
		$end_len = strlen($end);
		return $end_len > 0 ? substr($str, -$end_len) === $end: true;
	}

	function usage($invoke) {
		echo "Usage :: " . $invoke . " [-r] <program_name>.kf\n";
	}
?>
