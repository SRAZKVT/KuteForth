#!/usr/bin/php
<?php
	$iota = 0;
	$functions = array();
	$constants = array(); // Constants are not implemented yet
	$types = array("int", "void");

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
	define('KEYWORD_PLUS', 'plus');
	define('KEYWORD_MINUS', 'minus');
	define('KEYWORD_MULT', 'mult');
	define('KEYWORD_DIVMOD', 'divmod');
	define('KEYWORD_DROP', 'drop');
	define('KEYWORD_DUP', 'dup');
	define('KEYWORD_SWAP','swap');
	define('KEYWORD_ROT', 'rot');
	define('KEYWORD_OVER', 'over');
	define('KEYWORD_STACK_DUMPER', '???'); 

	define('OP_FUNCTION', iota(true));
	define('OP_RETURN', iota());
	define('OP_PUSH_INTEGER', iota());
	define('OP_CALL', iota());
	define('OP_SYSCALL0', iota());
	define('OP_SYSCALL1', iota());
	define('OP_SYSCALL2', iota());
	define('OP_SYSCALL3', iota());
	define('OP_SYSCALL4', iota());
	define('OP_SYSCALL5', iota());
	define('OP_SYSCALL6', iota());
	define('OP_PLUS', iota());
	define('OP_MINUS', iota());
	define('OP_MULT', iota());
	define('OP_DIVMOD', iota());
	define('OP_DROP', iota());
	define('OP_DUP', iota());
	define('OP_SWAP', iota());
	define('OP_ROT', iota());
	define('OP_OVER', iota());


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
		$basename = basename($filepath, ".kf");
		$tokens = getTokens($filepath);
		$inter_repr = getInterRepr($tokens);
		generate($inter_repr);

		$nasm = shell_exec("nasm -f elf64 output.asm -o output.o");
		$ld = shell_exec("ld -e _start -o output output.o");
		rename("output", $basename);
		unlink("output.o");
		if ($autorun) {
			$exit_code = 0;
			$output = array();
			exec("./" . $basename, $output, $exit_code);
			foreach ($output as $line) {
				echo $line . "\n";
			}
			exit($exit_code);
		}
	}

	/**
	* This function opens the designated file, parses each word, and returns an array of Tokens.
	*/
	function getTokens($filepath) {
		$prev_slash = false;
		$ret = array();
		$file = fopen($filepath, "r") or die ("ERROR: Unable to open the file " . $filepath);
		$code = fread($file, filesize($filepath));
		$lines = explode("\n", $code);
		$line_place = 0;
		foreach ($lines as $line) {
			$prev_slash = false;
			$line_place++;
			$char_place = 0;
			$chars = str_split($line);
			$current_word = "";
			foreach ($chars as $c) {
				$char_place++;
				if ($c === "/") {
					if ($prev_slash) {
						continue 2;
					}
					$prev_slash = true;
				} else $prev_slash = false;
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

		$implemented_functions = array();
		$in_function_def_in = false;
		$in_function_definition = false;
		$function_name_defined = false;
		$in_function = false;
		$function_name = "";
		$function_type_stack_in = array();
		$function_type_stack_out = array();
		$block_count = 0;

		$type_stack = array();

		foreach ($tokens as $token) {
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
					$in_function_def_in = true;
					$block_count++;
					continue 2;
					break;
				case KEYWORD_IN_BLOCK:
					if ($function_name === "main") {
						$function_name = "_start";
						if ($function_type_stack_in != array("void") || $function_type_stack_out != array("void")) {
							echo "[COMPILATION ERROR]: The main function should have void as input and output parameters\n" . $token->getTokenInformation() . "\n";
							exit(1);
						}
					}
					if (!sizeof($function_type_stack_in) > 0 || !sizeof($function_type_stack_out) > 0) {
						echo "[COMPILATION ERROR]: Type information missing for function `" . $function_name . "`\n" .$token->getTokenInformation() . "\n";
						exit(1);
					}
					$function_definition = new FunctionDef($function_name, $function_type_stack_in, $function_type_stack_out);
					$func_def = findFunctionByName($functions, $function_name);
					if ($func_def !== null && findFunctionByName($functions, $function_name) != $function_definition) {
						echo "[COMPILATION ERROR]: Type information mismatch for function `" . $function_name ."` Expected : ";
						foreach ($func_def->type_stack_in as $type) {
							echo $type . " ";
						}
						echo "-- ";
						foreach ($func_def->type_stack_out as $type) {
							echo $type . " ";
						}
						echo "but got : ";
						foreach ($function_definition->type_stack_in as $type) {
							echo $type . " ";
						}
						echo "-- ";
						foreach ($function_definition->type_stack_out as $type) {
							echo $type . " ";
						}
						echo "\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$inter_repr = new InterRepr(OP_FUNCTION, $function_definition);
					$function_type_stack_in = array();
					$function_type_stack_out = array();
					array_push($implemented_functions, $function_definition);
					if (findFunctionByName($functions, $function_name) === null) array_push($functions, $function_definition);
					$in_function_definition = false;
					$in_function = true;
					foreach ($function_definition->type_stack_in as $type) {
						if ($type !== "void") array_push($type_stack, $type);
					}
					break;
				case KEYWORD_END_BLOCK:
					if ($block_count < 1) {
						echo "[COMPILATION ERROR]: End has no block to close\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					if ($block_count == 1) {
						if (!$in_function_definition) {
							if ($function_name === "_start") {
								array_push($inter_repr_comp, new InterRepr(OP_PUSH_INTEGER, 0));
								array_push($inter_repr_comp, new InterRepr(OP_PUSH_INTEGER, 60));
								$inter_repr = new InterRepr(OP_SYSCALL1);
							} else {
								$inter_repr = new InterRepr(OP_RETURN);
							}
						} else {
							if ($function_name === "main") {
								$function_name = "_start";
								if ($function_type_stack_in != array("void") || $function_type_stack_out != array("void")) {
									echo "[COMPILATION ERROR]: The main function should have void as input and output parameters\n" . $token->getTokenInformation() . "\n";
									exit (1);
								}
							}
							array_push($functions, new FunctionDef($function_name, $function_type_stack_in, $function_type_stack_out));
							$function_type_stack_in = array();
							$function_type_stack_out = array();
							$in_function_def_in = true;
						}
						if ($in_function) {
							$out_func = findFunctionByName($functions, $function_name);
							foreach ($out_func->type_stack_out as $type) {
								if ($type === "void") {
									if (sizeof($type_stack) > 0) {
										echo "[COMPILATION ERROR]: Unhandled data on the stack in function `" . $function_name ."`\n" . $token->getTokenInformation() . "\n";
										echo "Found types still present on the stack:\n";
										foreach ($type_stack as $t) {
											echo $t . "\n";
										}
										exit(1);
									}
								} else {
									$t = array_pop($type_stack);
									if ($type !== $t) {
										if ($t === null) $t = "nothing";
										echo "[COMPILATION ERROR]: Unexpected data on the stack in function `" . $function_name . "` : expected `" . $type ."` but got `" . $t . "` instead\n";
										exit(1);
									}
								}
							}
						}
						$function_name = "";
						$in_function = false;
						$in_function_definition = false;
						$block_count--;
						$function_name_defined = false;
						if ($inter_repr === null) continue 2;
						$type_stack = array();
						break;
					} else {
						echo "[TODO]: Closing other than function blocks is not implemented yet";
						exit(1);
					}
				case KEYWORD_SYSCALL0:
					if (sizeof($type_stack) < 1) {
						echo "[COMPILATION ERROR]: Not enough arguments for syscall0\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t = array_pop($type_stack);
					if ($t !== "int") {
						echo "[COMPILATION ERROR]: Expected `int` for keyword `syscall0`, but instead got `" . $t . "`\n";
						exit(1);
					}
					$inter_repr = new InterRepr(OP_SYSCALL0);
					break;
				case KEYWORD_SYSCALL1:
					if (sizeof($type_stack) < 2) {
						echo "[COMPILATION ERROR]: Not enough arguments for syscall1\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t = array_pop($type_stack);
					if ($t !== "int") {
						echo "[COMPILATION ERROR]: Expected `int` for keyword `syscall1`, but instead got `" . $t . "`\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_pop($type_stack);
					$inter_repr = new InterRepr(OP_SYSCALL1);
					break;
				case KEYWORD_SYSCALL2:
					if (sizeof($type_stack) < 3) {
						echo "[COMPILATION ERROR]: Not enough arguments for syscall2\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t = array_pop($type_stack);
					if ($t !== "int") {
						echo "[COMPILATION ERROR]: Expected `int` for keyword `syscall2`, but instead got `" . $t . "`\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_pop($type_stack);
					array_pop($type_stack);
					$inter_repr = new InterRepr(OP_SYSCALL2);
					break;
				case KEYWORD_SYSCALL3:
					if (sizeof($type_stack) < 4) {
						echo "[COMPILATION ERROR]: Not enough arguments for syscall3\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t = array_pop($type_stack);
					if ($t !== "int") {
						echo "[COMPILATION ERROR]: Expected `int` for keyword `syscall3`, but instead got `" . $t . "`\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					$inter_repr = new InterRepr(OP_SYSCALL3);
					break;
				case KEYWORD_SYSCALL4:
					if (sizeof($type_stack) < 5) {
						echo "[COMPILATION ERROR]: Not enough arguments for syscall4\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t = array_pop($type_stack);
					if ($t !== "int") {
						echo "[COMPILATION ERROR]: Expected `int` for keyword `syscall4`, but instead got `" . $t . "`\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					$inter_repr = new InterRepr(OP_SYSCALL4);
					break;
				case KEYWORD_SYSCALL5:
					if (sizeof($type_stack) < 6) {
						echo "[COMPILATION ERROR]: Not enough arguments for syscall5\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t = array_pop($type_stack);
					if ($t !== "int") {
						echo "[COMPILATION ERROR]: Expected `int` for keyword `syscall5`, but instead got `" . $t . "`\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					$inter_repr = new InterRepr(OP_SYSCALL5);
					break;
				case KEYWORD_SYSCALL6:
					if (sizeof($type_stack) < 7) {
						echo "[COMPILATION ERROR]: Not enough arguments for syscall6\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t = array_pop($type_stack);
					if ($t !== "int") {
						echo "[COMPILATION ERROR]: Expected `int` for keyword `syscall6`, but instead got `" . $t . "`\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					array_pop($type_stack);
					$inter_repr = new InterRepr(OP_SYSCALL6);
					break;
				case KEYWORD_PLUS:
					if (sizeof($type_stack) < 2) {
						echo "[COMPILATION ERROR]: Not enough arguments for addition\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 !== "int" || $t2 !== "int") {
						echo "[COMPILATION ERROR]: Expected `int, int` for keyword `plus`, but instead got `" . $t1 . ", " . $t2 . "`\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_push($type_stack, "int");
					$inter_repr = new InterRepr(OP_PLUS);
					break;
				case KEYWORD_MINUS:
					if (sizeof($type_stack) < 2) {
						echo "[COMPILATION ERROR]: Not enough arguments for substraction\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 !== "int" || $t2 !== "int") {
						echo "[COMPILATION ERROR]: Expected `int, int` for keyword `minus`, but instead got `" . $t1 . ", " . $t2 . "`\n" . $token->getTokenInformation() . "\n";
					}
					array_push($type_stack, "int");
					$inter_repr = new InterRepr(OP_MINUS);
					break;
				case KEYWORD_MULT:
					if (sizeof($type_stack) < 2) {
						echo "[COMPILATION ERROR]: Not enough arguments for multiplication\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 !== "int" || $t2 !== "int") {
						echo "[COMPILATION ERROR]: Expected `int, int` for keyword `mult`, but instead got `" . $t1 . ",  " . $t2 . "`\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_push($type_stack, "int");
					$inter_repr = new InterRepr(OP_MULT);
					break;
				case KEYWORD_DIVMOD:
					if (sizeof($type_stack) < 2) {
						echo "[COMPILATION ERROR]: Not enough arguments for division and modulo\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 !== "int" || $t2 !== "int") {
						echo "[COMPILATION ERROR]: Expected `int, int` for keyword `divmod`, but instead got `" . $t1 . ", " . $t2 . "`\n" . $token->getTokenInformation() . "\n";
					}
					array_push($type_stack, "int");
					array_push($type_stack, "int");
					$inter_repr = new InterRepr(OP_DIVMOD);
					break;
				case KEYWORD_DROP:
					if (sizeof($type_stack) < 2) {
						echo "[COMPILATION ERROR]: Nothing to drop\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_pop($type_stack);
					$inter_repr = new InterRepr(OP_DROP);
					break;
				case KEYWORD_DUP:
					if (sizeof($type_stack) < 1) {
						echo "[COMPILATION ERROR]: Nothing to duplicate\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t = array_pop($type_stack);
					array_push($type_stack, $t);
					array_push($type_stack, $t);
					$inter_repr = new InterRepr(OP_DUP);
					break;
				case KEYWORD_SWAP:
					if (sizeof($type_stack) < 2) {
						echo "[COMPILATION ERROR]: Not enough elements to swap\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					array_push($type_stack, $t1);
					array_push($type_stack, $t2);
					$inter_repr = new InterRepr(OP_SWAP);
					break;
				case KEYWORD_ROT:
					if (sizeof($type_stack) < 3) {
						echo "[COMPILATION ERROR]: Not enough elements to rotate\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					$t3 = array_pop($type_stack);
					array_push($type_stack, $t2);
					array_push($type_stack, $t1);
					array_push($type_stack, $t3);
					$inter_repr = new InterRepr(OP_ROT);
					break;
				case KEYWORD_OVER:
					if (sizeof($type_stack) == 1) {
						echo "[COMPILATION ERROR]: Not element behind the top of the stack\n" . $token->getTokenInformation() . "\n";
						exit(1);
					} else if (sizeof(type_stack) < 1) {
						echo "[COMPILATION ERROR]: The stack is empty, \n" . $token->getTokenInformation() . "\n";
					}
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					array_push($type_stack, $t2);
					array_push($type_stack, $t1);
					array_push($type_stack, $t2);
					$inter_repr = new InterRepr(OP_OVER);
					break;
				case KEYWORD_STACK_DUMPER:
					echo "[DUMP STACK]: at " . $token->getTokenInformation() . "\n";
					foreach ($type_stack as $t) {
						echo $t . "\n";
					}
					exit(2);
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
							array_push($type_stack, "int");
						}
					} else {
						if ($in_function_definition) {
							$type = isAType($token->word);
							if (!$function_name_defined) {
								if ($type) { // TODO: Disallow using constants names as function names
									echo "[COMPILATION ERROR]: Types are not allowed as function names\n" . $token->getTokenInformation() . "\n";
									exit(1);
								} else if (in_array($token->word, $functions, false) && in_array($token->word, $implemented_functions, false)) {
									echo "[COMPILATION ERROR]: Redefinition of functions is not allowed\n" . $token->getTokenInformation() . "\n";
									exit(1);
								} else if ($token->word === "_start") {
									echo "[COMPILATION ERROR]: The name `_start` is forbidden for functions\n" . $token->getTokenInformation() . "\n";
									exit(1);
								}
								if (!isFunctionNameLegal($token->word)) {
									echo "[COMPILATION ERROR]: The function name `" . $token->word . "` is not legal\n" . $token->getTokenInformation() . "\n";
									exit(1);
								}
								$function_name_defined = true;
								$function_name = $token->word;
								continue 2;
							} else {
								if ($type) {
									if ($in_function_def_in) {
										array_push($function_type_stack_in, $token->word);
										continue 2;
									} else {
										array_push($function_type_stack_out, $token->word);
										continue 2;
									}
								} else {
									if ($token->word == "--") {
										$in_function_def_in = false;
										continue 2;
									}
									echo "[COMPILATION ERROR]: Only types are allowed once the function name is defined\n" . $token->getTokenInformation() . "\n";
									exit(1);
								}
							}
						} else if ($in_function) {
							if ($token->word === "main") $token->word = "_start";
							$fun = findFunctionByName($functions, $token->word);
							if($fun !== null) {
								$inter_repr = new InterRepr(OP_CALL, $token->word);
								$in_func_stack = $fun->type_stack_in;
								foreach ($in_func_stack as $exp_type) {
									if ($exp_type !== "void") {
										$type = array_pop($type_stack);
										if ($type === null) $type = "nothing";
										if ($type !== $exp_type) {
											echo "[COMPILATION ERROR]: Expected `" . $exp_type . "` but got `" . $type . "` instead\n" . $token->getTokenInformation() . "\n";
											exit(1);
										}
									}
								}
								$out_func_stack = $fun->type_stack_out;
								foreach ($out_func_stack as $exp_type) {
									if ($exp_type !== "void") {
										array_push($type_stack, $exp_type);
									}
								}
							} else {
								echo "[COMPILATION ERROR]: Unknown word\n" . $token->getTokenInformation() . "\n";
								exit(1);
							}
						} else {
							echo "[COMPILATION ERROR]: Words are not allowed outside of blocks" . $token->getTokenInformation() . "\n";
							exit(1);
						}
					}

			}
			if ($inter_repr === null) {
				echo "[ERROR]: Intermediary representation of token [" . $token->getTokenInformation() . "] is invalid\n";
				exit(1);
			}
			array_push($inter_repr_comp, $inter_repr);
		}
		if (findFunctionByName($functions, "_start") === null) {
			echo "[COMPILATION ERROR]: No entrypoint has been defined, the entrypoint has to be a function called `main`\n";
			exit(1);
		}
		foreach ($functions as $f) {
			if (findFunctionByName($implemented_functions, $f->name) === null) {
				echo "[COMPILATION ERROR]: Function `" . $f->name . "` has been defined, but no implementation has been provided\n";
				exit(1);
			}
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

	function findFunctionByName($functions, $name) {
		foreach ($functions as $f) if ($f->name === $name) return $f;
		return null;
	}

	function isAnInt($val) {
		$len = strlen($val);
		if ($len < 1) return false;
		return ctype_digit($val);
	}

	function isAType($val) {
		global $types;
		return in_array($val, $types, false);
	}

	class FunctionDef {
		public $name;
		public $type_stack_in;
		public $type_stack_out;
		
		function __construct($name, $type_stack, $type_stack_out) {
			$this->name = $name;
			$this->type_stack_in = $type_stack;
			$this->type_stack_out = $type_stack_out;
		}
	}

	function isFunctionNameLegal($name) {
		$allowedCharacters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_$#@~.?";
		$name_chr = str_split($name);
		$valid_chr = str_split($allowedCharacters);
		foreach ($name_chr as $c) if (!in_array($c, $valid_chr, false)) return false;
		return true;
	}

	function generate($inter_repr) {
		$current_function = null;
		$file = fopen("output.asm", "w") or die ("[ERROR]: Unable to open the output file\n");
		fwrite($file, "BITS 64\n");
		fwrite($file, "section .text\n");
		fwrite($file, "global _start\n");
		foreach ($inter_repr as $operation) {
			switch ($operation->op_code) {
				case OP_FUNCTION:
					fwrite($file, "\t;; OP_FUNCTION\n");
					fwrite($file, "\t" . $operation->value->name . ":\n");
					if ($operation->value->name !== "_start") fwrite($file, "\tpop r15\n"); // Pop function return to r15 register
					break;
				case OP_RETURN:
					fwrite($file, "\t;; OP_RETURN\n");
					fwrite($file, "\tpush r15\n");
					fwrite($file, "\tret\n");
					break;
				case OP_PUSH_INTEGER:
					fwrite($file, "\t;; OP_PUSH_INTEGER\n");
					fwrite($file, "\tmov rax, " . $operation->value . "\n");
					fwrite($file, "\tpush rax\n");
					break;
				case OP_CALL:
					fwrite($file, "\t;; OP_CALL\n");
					if ($operation->value === "main") $operation->value = "_start";
					fwrite($file, "\tcall " . $operation->value . "\n");
					break;
				case OP_SYSCALL0:
					fwrite($file, "\t;; OP_SYSCALL0\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tsyscall\n");
					break;
				case OP_SYSCALL1:
					fwrite($file, "\t;; OP_SYSCALL1\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tsyscall\n");
					break;
				case OP_SYSCALL2:
					fwrite($file, "\t;; OP_SYSCALL2\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rsi\n");
					fwrite($file, "\tsyscall\n");
					break;
				case OP_SYSCALL3;
					fwrite($file, "\t;; OP_SYSCALL3\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rsi\n");
					fwrite($file, "\tpop rdx\n");
					fwrite($file, "\tsyscall\n");
					break;
				case OP_SYSCALL4:
					fwrite($file, "\t;; OP_SYSCALL4\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rsi\n");
					fwrite($file, "\tpop rdx\n");
					fwrite($file, "\tpop r10\n");
					fwrite($file, "\tsyscall\n");
					break;
				case OP_SYSCALL5:
					fwrite($file, "\t;; OP_SYSCALL5\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rsi\n");
					fwrite($file, "\tpop rdx\n");
					fwrite($file, "\tpop r10\n");
					fwrite($file, "\tpop r8\n");
					fwrite($file, "\tsyscall\n");
					break;
				case OP_SYSCALL6:
					fwrite($file, "\t;; OP_SYSCALL6\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rsi\n");
					fwrite($file, "\tpop rdx\n");
					fwrite($file, "\tpop r10\n");
					fwrite($file, "\tpop r8\n");
					fwrite($file, "\tpop r9\n");
					fwrite($file, "\tsyscall\n");
					break;
				case OP_PLUS:
					fwrite($file, "\t;; OP_PLUS\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tadd rax, rdi\n");
					fwrite($file, "\tpush rax\n");
					break;
				case OP_MINUS:
					fwrite($file, "\t;; OP_MINUS\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tsub rax, rdi\n");
					fwrite($file, "\tpush rax\n");
					break;
				case OP_MULT:
					fwrite($file, "\t;; OP_MULT\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tmul rdi\n");
					fwrite($file, "\tpush rax\n");
					break;
				case OP_DIVMOD:
					fwrite($file, "\t;; OP_DIVMOD\n");
					fwrite($file, "\tmov rdi, 0\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tdiv rdi\n");
					fwrite($file, "\tpush rdx\n");
					fwrite($file, "\tpush rax\n");
					break;
				case OP_DROP:
					fwrite($file, "\t;; OP_DROP\n");
					fwrite($file, "\tpop rax\n");
					break;
				case OP_DUP:
					fwrite($file, "\t;; OP_DUP\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpush rax\n");
					fwrite($file, "\tpush rax\n");
					break;
				case OP_SWAP:
					fwrite($file, "\t;; OP_SWAP\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpush rax\n");
					fwrite($file, "\tpush rdi\n");
					break;
				case OP_ROT:
					fwrite($file, "\t;; OP_ROT\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpop rsi\n");
					fwrite($file, "\tpush rdi\n");
					fwrite($file, "\tpush rax\n");
					fwrite($file, "\tpush rsi\n");
					break;
				case OP_OVER:
					fwrite($file, "\t;; OP_OVER\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tpush rdi\n");
					fwrite($file, "\tpush rax\n");
					fwrite($file, "\tpush rdi\n");
					break;
			} 
		}
		fclose($file);
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
