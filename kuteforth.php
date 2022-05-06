#!/usr/bin/php
<?php
	$iota = 0;
	$functions = array();
	$constants = array(); // Constants are not implemented yet
	$types = array();

	define('KEYWORD_FUNCTION' ,'func');
	define('KEYWORD_IN_BLOCK' ,'in');
	define('KEYWORD_END_BLOCK' ,'end');
	define('KEYWORD_SYSCALL0' ,'syscall0');
	define('KEYWORD_SYSCALL1' ,'syscall1');
	define('KEYWORD_SYSCALL2' ,'syscall2');
	define('KEYWORD_SYSCALL3' ,'syscall3');
	define('KEYWORD_SYSCALL4' ,'syscall4');
	define('KEYWORD_SYSCALL5' ,'syscall5');
	define('KEYWORD_SYSCALL6' ,'syscall6');

	define('OP_FUNCTION', iota(true));
	define('OP_IN_BLOCK', iota());
	define('OP_RETURN', iota());
	define('OP_PUSH_INTEGER', iota());
	define('OP_CALL', iota());
	define('OP_SYSCALL0', iota());
	define('OP_SYSCALL1', iota());
	define('OP_SYSCALL2', iota());
	define('OP_SYSCALL3', iota());
	define('OP_SYSCALL4', iota());
	define('OP_SYSCALL5', iota());



	main($argv);

	function main($argv) {

		
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
		$inter_repr = getInterRepr($tokens);

	}

	/**
	* This function opens the designated file, parses each word, and returns an array of Tokens.
	*/
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
			if ($current_word !== "") {
				$token = new Token($current_word, $line_place, $char_place - strlen($current_word), $filepath);
				array_push($ret, $token);
			}
									
		}
		fclose($file);
		return $ret;
	}

	/**
	* This class represents a token : it contains it's word, and location information, both depending on which file it is present, but also where in that file.
	*/
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

	function getInterRepr($tokens) {
		global $functions;
		
		$inter_repr_comp = array();

		$in_function_definition = false;
		$function_name_defined = false;
		$in_function = false;
		$function_name = "";
		$function_type_stack = array();

		

		$block_count = 0;

		foreach ($tokens as $token) {
			echo $token->word . "\n";
			$inter_repr = null;
			switch ($token->word) {
				case KEYWORD_FUNCTION:
					if ($in_function || $in_function_definition) {
						echo "[COMPILATION ERROR]: Function definitions are not allowed inside of other functions\n" . $token->getTokenInformation() . "\n";
						exit(1);
					} else if ($block_count > 0) {
						echo "[COMPILATION ERROR]: Functions definitions are only allowed outside of any block\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$in_function_definition = true;
					continue 2;
					break;
				case KEYWORD_IN_BLOCK:
					$inter_repr = new InterRepr(OP_FUNCTION, new FunctionDef($function_name, $function_type_stack));
					array_push($functions, $function_name);
					$function_name = "";
					$function_type_stack = array();
					$in_function_definition = false;
					$in_function = true;
					$block_count++;
					break;
				case KEYWORD_END_BLOCK:
					if ($block_count < 1) {
						echo "[COMPILATION ERROR]: End has no block to close\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					if ($block_count == 1) {
						$inter_repr = new InterRepr(OP_RETURN);
						break;
					} else {
						echo "[TODO]: Closing other than function blocks is not implemented yet";
						exit(1);
					}
				case KEYWORD_SYSCALL0:
					echo "Keyword syscall0 is not implemented yet\n";
					exit(1);
				case KEYWORD_SYSCALL1:
					$inter_repr = new InterRepr(OP_SYSCALL1);
					break;
				default:
					if (isAnInt($token->word)) {
						if ($in_function_definition) {
							echo "[COMPILATION ERROR]: Integers are not allowed within function definitions\n" . $token->getTokenInformation() . "\n";
							exit(1);
						}
						if ($block_count <= 0) {
							echo "[COMPILATION ERROR]: Numbers are only allowed within code blocks\n" . $token->getTokenInformation() . "\n";
							exit(1);
						}
						if ($in_function) {
							$value = (int) $token->word;
							$inter_repr = new InterRepr(OP_PUSH_INTEGER, $value);
						}
					} else {
						if ($in_function_definition) {
							$type = isAType($token->word);
							if (!$function_name_defined) {
								if ($type) { // TODO: Disallow using constants names as function names
									echo "[COMPILATION ERROR]: Types are not allowed as function names\n" . $token->getTokenInformation() . "\n";
									exit(1);
								} else if (in_array($token->word, $functions, false)) {
									echo "[COMPILATION ERROR]: Redefinition of functions is not allowed\n" . $token->getTokenInformation() . "\n";
									exit(1);
								}
								$function_name_defined = true;
								$function_name = $token->word;
								continue 2;
							} else {
								if ($type) {
									array_push($function_type_stack, $token->word);
									continue 2;
								} else {
									echo "[COMPILATION ERROR]: Only types are allowed once the function name is defined\n" . $token->getTokenInformation() . "\n";
									exit(1);
								}
							}
						} else if ($in_function) {
							if(in_array($token->word, $functions, false)) {
								$inter_repr = new InterRepr(OP_PUSH_INTEGER, $value);
							} else {
								echo "[COMPILATION ERROR]: Uknown word\n" . $token->getTokenInformation() . "\n";
							}
						} else {
							echo "[COMPILATION ERROR]: Words are not allowed outside of blocks" . $token->getTokenInformation() . "\n";
						}
					}

			}
			if ($inter_repr === null) {
				echo "[ERROR]: Intermediary representation of token [" . $token->getTokenInformation() . "] is invalid\n";
				exit(1);
			}
			array_push($inter_repr_comp, $inter_repr);
		}
		return $inter_repr_comp;
	}

	class InterRepr {
		public $op_code;
		public $value;

		function __construct($op_code, $value=null) {
			$this->op_code = $op_code;
			$this->value = $value;
		}
	}

	function iota($reset=false) {
		global $iota;
		if ($reset) $iota = 0;
		$val = $iota;
		$iota++;
		return $val;
	}

	function isAnInt($val) {
		$len = strlen($val);
		if ($len < 1) return false;
		if ($len === 1 && $val === "-") return false;
		$chars = str_split($val);
		$pos = 0;
		foreach ($chars as $c) {
			if ($c === "-" && $pos === 0) continue;
			if (ctype_digit($c)) continue;
			else return false;
		}
		return true;
	}

	function isAType($val) {
		global $types;
		return in_array($val, $types, false);
	}

	class FunctionDef {
		public $name;
		public $type_stack;
		
		function __construct($name, $type_stack) {
			$this->name = $name;
			$this->type_stack = $type_stack;
		}
	}
		

	/**
	* Returns true if the character specified in $c is a whitespace character, and false otherwise
	*/
	function isWhitespace($c) {
		return ($c === ' ' || $c === "\n" || $c === "\t" || $c === '\r');
	}

	/**
	* Returns true if the string specified in $str ends with $end, and false otherwise
	*/
	function endsWith($str, $end) {
		$end_len = strlen($end);
		return $end_len > 0 ? substr($str, -$end_len) === $end: true;
	}

	/**
	* Returns true if the string specified in $str starts with $start, and false otherwise
	*/
	function startsWith($str, $start) {
		$start_len = strlen($start);
		return $start_len > 0 ? $start  . substr($str, $start_len) === $str: true;
	}

	/**
	* Prints the usage of the command.
	*/
	function usage($invoke) {
		echo "Usage :: " . $invoke . " [-r] <program_name>.kf\n";
	}
?>
