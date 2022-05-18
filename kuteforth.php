#!/usr/bin/php
<?php
	$iota = 0;
	$functions = array();
	$constants = array(); // Constants are not implemented yet

	define('KEYWORD_FUNCTION' ,'func');     iota(true);
	define('KEYWORD_IN_BLOCK' ,'in');       iota();
	define('KEYWORD_END_BLOCK' ,'end');     iota();
	define('KEYWORD_IF', 'if');             iota();
	define('KEYWORD_DO', 'do');             iota();
	define('KEYWORD_ELSE', 'else');         iota();
	define('KEYWORD_ELIF', 'elif');         iota();
	define('KEYWORD_WHILE', 'while');       iota();
	define('KEYWORD_SYSCALL0' ,'syscall0'); iota();
	define('KEYWORD_SYSCALL1' ,'syscall1'); iota();
	define('KEYWORD_SYSCALL2' ,'syscall2'); iota();
	define('KEYWORD_SYSCALL3' ,'syscall3'); iota();
	define('KEYWORD_SYSCALL4' ,'syscall4'); iota();
	define('KEYWORD_SYSCALL5' ,'syscall5'); iota();
	define('KEYWORD_SYSCALL6' ,'syscall6'); iota();
	define('KEYWORD_PLUS', 'plus');         iota();
	define('KEYWORD_MINUS', 'minus');       iota();
	define('KEYWORD_MULT', 'mult');         iota();
	define('KEYWORD_DIVMOD', 'divmod');     iota();
	define('KEYWORD_EQ', 'eq');             iota();
	define('KEYWORD_NOT', 'not');           iota();
	define('KEYWORD_DROP', 'drop');         iota();
	define('KEYWORD_DUP', 'dup');           iota();
	define('KEYWORD_SWAP','swap');          iota();
	define('KEYWORD_ROT', 'rot');           iota();
	define('KEYWORD_OVER', 'over');         iota();
	define('KEYWORD_STACK_DUMPER', '???');  iota();
	define('KEYWORD_COUNT',                 iota());

	define('OP_FUNCTION',       iota(true));
	define('OP_RETURN',         iota());
	define('OP_PUSH_INTEGER',   iota());
	define('OP_CALL',           iota());
	define('OP_DO',             iota());
	define('OP_LABEL',          iota());
	define('OP_JMP',            iota());
	define('OP_SYSCALL0',       iota());
	define('OP_SYSCALL1',       iota());
	define('OP_SYSCALL2',       iota());
	define('OP_SYSCALL3',       iota());
	define('OP_SYSCALL4',       iota());
	define('OP_SYSCALL5',       iota());
	define('OP_SYSCALL6',       iota());
	define('OP_PLUS',           iota());
	define('OP_MINUS',          iota());
	define('OP_MULT',           iota());
	define('OP_DIVMOD',         iota());
	define('OP_EQ',             iota());
	define('OP_NOT',            iota());
	define('OP_DROP',           iota());
	define('OP_DUP',            iota());
	define('OP_SWAP',           iota());
	define('OP_ROT',            iota());
	define('OP_OVER',           iota());
	define('OP_STACK_DUMPER',   iota());
	define('OP_ENTER_BLOCK',    iota());
	define('OP_LEAVE_BLOCK',    iota());
	define('OP_COUNT',          iota());

	define('TYPE_VOID',     'void');    iota(true);
	define('TYPE_INT',      'int');     iota();
	define('TYPE_BOOL',     'bool');    iota();
	define('TYPE_COUNT',    iota());
	$types = array(TYPE_VOID, TYPE_INT, TYPE_BOOL);

	define('BLOCK_IF',              iota(true));
	define('BLOCK_WHILE',           iota());
	define('BLOCK_MULT_BODY_IF',    iota());
	define('BLOCK_DO',              iota());
	define('BLOCK_FUNC',            iota());

	define('CALL_STACK_SIZE', 512 * 8);

	main($argv);

	function main($argv) {

		
		if (count($argv) < 2) {
			usage($argv[0]);
			exit(1);
		}
		$filepath;
		$autorun = $argv[1] == "-r";
		$dump = $argv[1] === "-d";
		if ($autorun || $dump) {
			$filepath = $argv[2];
		} else {
			$filepath = $argv[1];
		}
		if (!endsWith($filepath, ".kf")) {
			usage($argv[0]);
			exit(1);
		}
		$basename = basename($filepath, ".kf");
		
		$ts = microtime(true);
		$tokens = getTokens($filepath);
		$te = microtime(true);
		$t = $te - $ts;
		echo "[INFO]: Separating words took {$t}s\n";
		$total = $t;

		$ts = microtime(true);
		$inter_repr = getInterRepr($tokens);
		$te = microtime(true);
		$t = $te - $ts;
		echo "[INFO]: Parsing tokens took {$t}s\n";
		$total += $t;

		$ts = microtime(true);
		typeChecking($inter_repr);
		$te = microtime(true);
		$t = $te - $ts;
		echo "[INFO]: Type-checking took {$t}s\n";
		$total += $t;

		// TODO: DCE

		if (OP_COUNT != 28) {
			echo "[ERROR]: Unhandled op_codes in dump, there are now " . OP_COUNT . "\n";
			exit(127);
		}
		if ($dump) {
			echo "[DUMP]: Here is the intermediary representation of the program\n";
			foreach ($inter_repr as $ir) {
				switch ($ir->op_code) {
					case OP_FUNCTION:
						echo "OP_FUNCTION : " . $ir->value->name . " -> ";
						foreach ($ir->value->type_stack_in as $t) echo $t . " ";
						echo "-- ";
						foreach ($ir->value->type_stack_out as $t) echo $t . " ";
						echo "\n";
						break;
					case OP_RETURN:
						echo "OP_RETURN\n";
						break;
					case OP_PUSH_INTEGER:
						echo "OP_PUSH_INTEGER : " . $ir->value . "\n";
						break;
					case OP_CALL:
						echo "OP_CALL : " . $ir->value . "\n";
						break;
					case OP_DO:
						echo "OP_DO : " . $ir->value . "\n";
						break;
					case OP_LABEL:
						echo "OP_LABEL : " . $ir->value . "\n";
						break;
					case OP_JMP:
						echo "OP_JMP : " . $ir->value . "\n";
						break;
					case OP_SYSCALL0:
						echo "OP_SYSCALL0\n";
						break;
					case OP_SYSCALL1:
						echo "OP_SYSCALL1\n";
						break;
					case OP_SYSCALL2:
						echo "OP_SYSCALL2\n";
						break;
					case OP_SYSCALL3:
						echo "OP_SYSCALL3\n";
						break;
					case OP_SYSCALL4:
						echo "OP_SYSCALL4\n";
						break;
					case OP_SYSCALL5:
						echo "OP_SYSCALL5\n";
						break;
					case OP_SYSCALL6:
						echo "OP_SYSCALL6\n";
						break;
					case OP_PLUS:
						echo "OP_PLUS\n";
						break;
					case OP_MINUS:
						echo "OP_MINUS\n";
						break;
					case OP_MULT:
						echo "OP_MULT\n";
						break;
					case OP_DIVMOD:
						echo "OP_DIVMOD\n";
						break;
					case OP_EQ:
						echo "OP_EQ\n";
						break;
					case OP_NOT:
						echo "OP_NOT\n";
						break;
					case OP_DROP:
						echo "OP_DROP\n";
						break;
					case OP_DUP:
						echo "OP_DUP\n";
						break;
					case OP_SWAP:
						echo "OP_SWAP\n";
						break;
					case OP_ROT:
						echo "OP_ROT\n";
						break;
					case OP_OVER:
						echo "OP_OVER\n";
						break;
					case OP_ENTER_BLOCK:
						echo "OP_ENTER_BLOCK\n";
						break;
					case OP_LEAVE_BLOCK:
						echo "OP_LEAVE_BLOCK\n";
						break;
				}
			}
			exit(0);
		}


		$ts = microtime(true);
		generate($inter_repr);
		$nasm = shell_exec("nasm -f elf64 output.asm -o output.o");
		$ld = shell_exec("ld -e _start -o output output.o");
		rename("output", $basename);
		unlink("output.o");
		$te = microtime(true);
		$t = $te - $ts;
		echo "[INFO]: Generation took {$t}s\n";
		$total += $t;
		echo "[INFO]: Total took {$t}s\n";

		if ($autorun) {
			echo "[Info]: Running the program\n";
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

		$block_type = array();

		$while_stack = array(); // TODO: This is extremely dirty, atm need to have a new stack for each block created, should refactor to smth that makes more sense.
		$current_blocks = array();
		$implemented_functions = array();
		$in_function_def_in = false;
		$in_function_definition = false;
		$function_name_defined = false;
		$in_function = false;
		$function_name = "";
		$function_type_stack_in = array();
		$function_type_stack_out = array();
		$block_count = 0;
		$do_depth = 0;
		$jump_stack = array();
		$jmp_end_stacks = array();
		$jmp_nb = 0;
		$condition_def = false;


		if (KEYWORD_COUNT != 27) {
			echo "[ERROR]: Unhandled keywords, there are now " . KEYWORD_COUNT . "keywords\n";
			exit(127);
		}
		foreach ($tokens as $token) {
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
					if (
					(in_array("void", $function_type_stack_in, false) && sizeof($function_type_stack_in) != 1) || (in_array("void", $function_type_stack_out, false) && sizeof($function_type_stack_out) != 1)) {
						echo "[COMPILATION ERROR]: Illegal type arguments for function " . $function_name . "\n" . $token->getTokenInformation() . "\n";
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
					array_push($inter_repr_comp, new InterRepr(OP_FUNCTION, $function_definition, $token));
					array_push($inter_repr_comp, new InterRepr(OP_ENTER_BLOCK, BLOCK_FUNC, $token));
					$function_type_stack_in = array();
					$function_type_stack_out = array();
					array_push($implemented_functions, $function_definition);
					if (findFunctionByName($functions, $function_name) === null) array_push($functions, $function_definition);
					$in_function_definition = false;
					$in_function = true;
					$jmp_nb = 0;
					break;
				case KEYWORD_END_BLOCK:
					$block = array_pop($current_blocks);
					if ($block !== BLOCK_WHILE) array_push($current_blocks, $block);
					if ($block_count < 1) {
						echo "[COMPILATION ERROR]: End has no block to close\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					if ($block_count == 1) {
						if (!$in_function_definition) {
							if ($function_name === "_start") {
								array_push($inter_repr_comp, new InterRepr(OP_PUSH_INTEGER, 0, $token));
								array_push($inter_repr_comp, new InterRepr(OP_PUSH_INTEGER, 60, $token));
								array_push($inter_repr_comp, new InterRepr(OP_SYSCALL1, null, $token));
							}
							array_push($inter_repr_comp, new InterRepr(OP_RETURN, null, $token));
							
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
						$block_count--;
						$function_name = "";
						$in_function = false;
						$in_function_definition = false;
						$function_name_defined = false;
						break;
					} else if ($block === BLOCK_WHILE) {
						array_push($inter_repr_comp, new InterRepr(OP_JMP, $inter_repr_comp[array_pop($while_stack)]->value, $token));
						$jmp = $function_name . "jmp" . $jmp_nb;
						$jmp_nb++;
						array_push($inter_repr_comp, new InterRepr(OP_LABEL, $jmp, $token));

						if (isset($jump_stack[$block_count])) $inter_repr_comp[$jump_stack[$block_count]]->value = $jmp;
						
						$block_count--;
					} else if ($do_depth > 0) {
						$jmp = $function_name . "jmp" . $jmp_nb;
						$jmp_nb++;
						array_push($inter_repr_comp, new InterRepr(OP_LABEL, $jmp, $token));
						if (isset($jump_stack[$block_count])) $inter_repr_comp[$jump_stack[$block_count]]->value = $jmp;
						if (isset($jmp_end_stacks[$block_count])) {
							$end = array_pop($jmp_end_stacks[$block_count]);
							while ($end !== null) {
								$inter_repr_comp[$end]->value = $jmp;
								$end = array_pop($jmp_end_stacks[$block_count]);
							}
						}
						$do_depth--;
						$block_count--;
					} else {
						echo "[ERROR]: Unhandled block closing\n";
						exit(69);
					}
					array_push($inter_repr_comp, new InterRepr(OP_LEAVE_BLOCK, null, $token));
					break;
				case KEYWORD_IF:
					array_push($current_blocks, BLOCK_IF);
					array_push($inter_repr_comp, new InterRepr(OP_ENTER_BLOCK, BLOCK_IF, $token));
					$block_count++;
					$condition_def = true;
					continue 2;
					break;
				case KEYWORD_DO:
					if (!$condition_def) {
						echo "[COMPILATION ERROR]: Do cannot be called outside of a conditional\n" . $token->getTokenInformation() . "\n";
					}
					$do_depth++;
					array_push($inter_repr_comp, new InterRepr(OP_ENTER_BLOCK, BLOCK_DO, $token));
					$jump_stack[$block_count] = sizeof($inter_repr_comp);
					array_push($inter_repr_comp, new InterRepr(OP_DO, null, $token));
					$condition_def = false;
					break;
				case KEYWORD_ELSE:
					if ($do_depth < 1) {
						echo "[COMPILATION ERROR]: Else has no condition to close\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					array_push($inter_repr_comp, new InterRepr(OP_ENTER_BLOCK, BLOCK_MULT_BODY_IF, $token));
					if (!isset($jmp_end_stacks[$block_count])) $jmp_end_stacks[$block_count] = array();
					array_push($jmp_end_stacks[$block_count], sizeof($inter_repr_comp));
					array_push($inter_repr_comp, new InterRepr(OP_JMP, null, $token));
					$jmp = $function_name . "jmp" . $jmp_nb;
					$jmp_nb++;
					$inter_repr_comp[$jump_stack[$block_count]]->value = $jmp;
					$jump_stack[$block_count] = null;
					array_push($inter_repr_comp, new InterRepr(OP_LABEL, $jmp, $token));
					break;
				case KEYWORD_ELIF:
					if ($do_depth < 1) {
						echo "[COMPILATION ERROR]: Elif has no condition to close\n" . $token->getTokenInformation() . "\n";
						exit(1);
					}
					$do_depth--;
					array_push($inter_repr_comp, new InterRepr(OP_ENTER_BLOCK, BLOCK_MULT_BODY_IF, $token));
					if (!isset($jmp_end_stacks[$block_count])) $jmp_end_stacks[$block_count] = array();
					array_push($jmp_end_stacks[$block_count], sizeof($inter_repr_comp));
					array_push($inter_repr_comp, new InterRepr(OP_JMP, null, $token));
					$jmp = $function_name . "jmp" . $jmp_nb;
					$jmp_nb++;
					$inter_repr_comp[$jump_stack[$block_count]]->value = $jmp;
					$jump_stack[$block_count] = null;
					array_push($inter_repr_comp, new InterRepr(OP_LABEL, $jmp, $token));
					$condition_def = true;
					break;
				case KEYWORD_WHILE:
					array_push($current_blocks, BLOCK_WHILE);
					array_push($inter_repr_comp, new InterRepr(OP_ENTER_BLOCK, BLOCK_WHILE, $token));
					$jmp = $function_name . "jmp" . $jmp_nb;
					$jmp_nb++;
					array_push($while_stack, sizeof($inter_repr_comp));
					array_push($inter_repr_comp, new InterRepr(OP_LABEL, $jmp, $token));
					$block_count++;
					$condition_def = true;
					continue 2;
					break;
				case KEYWORD_SYSCALL0:
					array_push($inter_repr_comp, new InterRepr(OP_SYSCALL0, null, $token));
					break;
				case KEYWORD_SYSCALL1:
					array_push($inter_repr_comp, new InterRepr(OP_SYSCALL1, null, $token));
					break;
				case KEYWORD_SYSCALL2:
					array_push($inter_repr_comp, new InterRepr(OP_SYSCALL2, null, $token));
					break;
				case KEYWORD_SYSCALL3:
					array_push($inter_repr_comp, new InterRepr(OP_SYSCALL3, null, $token));
					break;
				case KEYWORD_SYSCALL4:
					array_push($inter_repr_comp, new InterRepr(OP_SYSCALL4, null, $token));
					break;
				case KEYWORD_SYSCALL5:
					array_push($inter_repr_comp, new InterRepr(OP_SYSCALL5, null, $token));
					break;
				case KEYWORD_SYSCALL6:
					array_push($inter_repr_comp, new InterRepr(OP_SYSCALL6, null, $token));
					break;
				case KEYWORD_PLUS:
					array_push($inter_repr_comp, new InterRepr(OP_PLUS, null, $token));
					break;
				case KEYWORD_MINUS:
					array_push($inter_repr_comp, new InterRepr(OP_MINUS, null, $token));
					break;
				case KEYWORD_MULT:
					array_push($inter_repr_comp, new InterRepr(OP_MULT, null, $token));
					break;
				case KEYWORD_DIVMOD:
					array_push($inter_repr_comp, new InterRepr(OP_DIVMOD, null, $token));
					break;
				case KEYWORD_EQ:
					array_push($inter_repr_comp, new InterRepr(OP_EQ, null, $token));
					break;
				case KEYWORD_DROP:
					array_push($inter_repr_comp, new InterRepr(OP_DROP, null, $token));
					break;
				case KEYWORD_NOT:
					array_push($inter_repr_comp , new InterRepr(OP_NOT, null, $token));
					break;
				case KEYWORD_DUP:
					array_push($inter_repr_comp, new InterRepr(OP_DUP, null, $token));
					break;
				case KEYWORD_SWAP:
					array_push($inter_repr_comp, new InterRepr(OP_SWAP, null, $token));
					break;
				case KEYWORD_ROT:
					array_push($inter_repr_comp, new InterRepr(OP_ROT, null, $token));
					break;
				case KEYWORD_OVER:
					array_push($inter_repr_comp, new InterRepr(OP_OVER, null, $token));
					break;
				case KEYWORD_STACK_DUMPER:
					array_push($inter_repr_comp, new InterRepr(OP_STACK_DUMPER, null, $token));
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
							array_push($inter_repr_comp, new InterRepr(OP_PUSH_INTEGER, $value, $token));
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
								array_push($inter_repr_comp, new InterRepr(OP_CALL, $token->word, $token));
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
		public $token;

		function __construct($op_code, $value, $token) {
			$this->op_code = $op_code;
			$this->token = $token;
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

	function typeCheckError($reason, $info) {
		echo "[TYPE-CHECKING ERROR]: " . $reason . "\n";
		echo $info . "\n";
		exit(1);
	}

	function todo($message) {
		echo "[TODO]: " . $message . "\n";
		exit(1);
	}

	function typeChecking($inter_repr) {
		global $functions;

		$mult_body_if = array();
		$saved_type_stack = array();
		$type_stack = array();
		$prev_type_stack = array();
		$block_stack = array();
		$function_ret_stack = array();
		$do_no_save = false;

		foreach ($inter_repr as $ir) {
			$token = $ir->token;
			switch ($ir->op_code) {
				case OP_STACK_DUMPER:
					echo "[STACK DUMPER]: The types currently on the stack are:\n" . getHumanReadableTypes($type_stack) . "\n";
					exit(10);
					break;
				case OP_ENTER_BLOCK:
					if ($ir->value === BLOCK_FUNC) {
						array_push($block_stack, BLOCK_FUNC);
					} else if ($ir->value === BLOCK_WHILE) {
						array_push($block_stack, BLOCK_WHILE);
						echo getHumanReadableTypes($type_stack) . "\n";
						$do_no_save = true;
						array_push($saved_type_stack, $type_stack);
					} else if ($ir->value === BLOCK_IF) {
						array_push($mult_body_if, false);
						array_push($block_stack, BLOCK_IF);
					} else if ($ir->value === BLOCK_MULT_BODY_IF) {
						if (array_pop($mult_body_if)) {
							$prev = array_pop($prev_type_stack);
							if ($type_stack !== $prev) typeCheckError("Mismatched type stacks : Expected `" . getHumanReadableTypes($prev) . "` but got `" . getHumanReadableTypes($type_stack) . "`", $token->getTokenInformation());
						}
						array_push($mult_body_if, true);
						array_push($prev_type_stack, $type_stack);
						$type_stack = array_pop($saved_type_stack);
						//todo("type checking for not multi body if implemented yet");
					} else if ($ir->value === BLOCK_DO) {
						array_pop($block_stack);
						array_push($block_stack, BLOCK_DO);
						if (!$do_no_save) {
							$sz = sizeof($saved_type_stack);
							array_push($saved_type_stack, $type_stack);
							array_pop($saved_type_stack[$sz]);
						}
						$do_no_save = false;
					} else {
						todo("This block is not recognised : " . $ir->value);
					}
					break;
				case OP_LEAVE_BLOCK:
					$b = array_pop($block_stack);
					if ($b === BLOCK_FUNC) {
						// do things
					} else if ($b == BLOCK_DO) {
						$prev;
						if (!array_pop($mult_body_if)) $prev = array_pop($saved_type_stack);
						else $prev = array_pop($prev_type_stack);
						if ($type_stack !== $prev) typeCheckError("Mismatched type stacks, got `" . getHumanReadableTypes($prev) . "` and `" . getHumanReadableTypes($type_stack) . "`", $token->getTokenInformation());
					} else if ($b == BLOCK_MULT_BODY_IF) {
						// this shouldn't do anything
					} else {
						todo("This block is not recognised : " . $b);
					}
					break;
				case OP_FUNCTION:
					$type_stack = array();
					foreach (getTypesFromHumanReadable($ir->value->type_stack_in) as $t) array_push($type_stack, $t);
					$function_ret_stack = array();
					foreach (getTypesFromHumanReadable($ir->value->type_stack_out) as $t) array_push($function_ret_stack, $t);
					
					break;
				case OP_PUSH_INTEGER:
					array_push($type_stack, TYPE_INT);
					break;
				case OP_RETURN:
					if ($type_stack !== $function_ret_stack) typeCheckError("Mismatched type stacks : expected `" . getHumanReadableTypes($function_ret_stack) . "` but got `" . getHumanReadableTypes($type_stack) . "`", $token->getTokenInformation());
					break;
				case OP_CALL:
					$savstack = $type_stack;
					$funcdef = findFunctionByName($functions, $ir->value);
					$in = getTypesFromHumanReadable($funcdef->type_stack_in);
					$out = getTypesFromHumanReadable($funcdef->type_stack_out);
					$e = array_pop($in);
					while ($e != null) {
						$t = array_pop($type_stack);
						if ($t !== $e) typeCheckError("Mismatched type stacks for function call : expected `" . getHumanReadableTypes($e) . "` but got `" . getHumanReadableTypes($savstack) . "`", $token->getTokenInformation());
						$e = array_pop($in);
					}
					$e = array_pop($out);
					while ($e != null) {
						array_push($type_stack, $e);
						$e = array_pop($out);
					}
					break;
				case OP_DO:
					if (sizeof($type_stack) < 1) typeCheckError("OP_DO requires a boolean, but got nothing", $token->getTokenInformation());
					$t = array_pop($type_stack);
					if ($t !== TYPE_BOOL) typeCheckError("OP_DO requires a boolean, but got an `" . getHumanReadableTypes(array($t)) . "`", $token->getTokenInformation());
					break;
				case OP_SYSCALL0:
					if (sizeof($type_stack) < 1) typeCheckError("Not enough arguments for OP_SYSCALL0", $token->getTokenInformation());
					array_pop($type_stack);
					break;
				case OP_SYSCALL1:
					if (sizeof($type_stack) < 2) typeCheckError("Not enough arguments for OP_SYSCALL1", $token->getTokenInformation());
					for ($i = 0; $i != 2; $i++) array_pop($type_stack);
					break;
				case OP_SYSCALL2;
					if (sizeof($type_stack) < 3) typeCheckError("Not enough arguments for OP_SYSCALL2", $token->getTokenInformation());
					for ($i = 0; $i != 3; $i++) array_pop($type_stack);
					break;
				case OP_SYSCALL3:
					if (sizeof($type_stack) < 4) typeCheckError("Not enough arguments for OP_SYSCALL3", $token->getTokenInformation());
					for ($i = 0; $i != 4; $i++) array_pop($type_stack);
					break;
				case OP_SYSCALL4:
					if (sizeof($type_stack) < 5) typeCheckError("Not enough arguments for OP_SYSCALL4", $token->getTokenInformation());
					for ($i = 0; $i != 5; $i++) array_pop($type_stack);
					break;
				case OP_SYSCALL5:
					if (sizeof($type_stack) < 6) typeCheckError("Not enough arguments for OP_SYSCALL5", $token->getTokenInformation());
					for ($i = 0; $i != 6; $i++) array_pop($type_stack);
					break;
				case OP_SYSCALL6:
					if (sizeof($type_stack) < 7) typeCheckError("Not enough arguments for OP_SYSCALL6", $token->getTokenInformation());
					for ($i = 0; $i != 7; $i++) array_pop($type_stack);
					break;
				case OP_PLUS:
					if (sizeof($type_stack) < 2) typeCheckError("Not enough arguments for OP_PLUS", $token->getTokenInformation());
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 == TYPE_INT && $t2 == TYPE_INT) array_push($type_stack, TYPE_INT);
					else typeCheckError("Unsupported operation with OP_PLUS : " . getHumanReadableTypes(array($t1, $t2)), $token->getTokenInformation());
					break;
				case OP_MINUS:
					if (sizeof($type_stack) < 2) typeCheckError("Not enough arguments for OP_MINUS", $token->getTokenInformation());
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 == TYPE_INT && $t2 == TYPE_INT) array_push($type_stack, TYPE_INT);
					else typeCheckError("Unsupported operation with OP_PLUS : " . getHumanReadableTypes(array($t1, $t2)), $token->getTokenInformation());
					break;
				case OP_MULT:
					if (sizeof($type_stack) < 2) typeCheckError("Not enough arguments for OP_MULT", $token->getTokenInformation());
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 == TYPE_INT && $t2 == TYPE_INT) array_push($type_stack, TYPE_INT);
					else typeCheckError("Unsupported operation with OP_MULT : " . getHumanReadableTypes(array($t1, $t2)), $token->getTokenInformation());
					break;
				case OP_DIVMOD:
					if (sizeof($type_stack) < 2) typeCheckError("Not enough arguments for OP_DIVMOD", $token->getTokenInformation());
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 == TYPE_INT && $t2 == TYPE_INT) {
						array_push($type_stack, TYPE_INT);
						array_push($type_stack, TYPE_INT);
					}
					else typeCheckError("Unsupported operation with OP_DIVMOD : " . getHumanReadableTypes(array($t1, $t2)), $token->getTokenInformation());
					break;
				case OP_EQ:
					if (sizeof($type_stack) < 2) typeCheckError("Not enough elements to compare with OP_EQ", $token->getTokenInformation());
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					if ($t1 === $t2) array_push($type_stack, TYPE_BOOL);
					else typeCheckError("Different types for equality check, requires same type : " . getHumanReadableTypes(array($t1, $t2)), $token->getTokenInformation());
					break;
				case OP_NOT:
					if (sizeof($type_stack) < 1) typeCheckError("No element to invert with OP_NOT", $token->getTokenInformation());
					$t = array_pop($type_stack);
					if ($t === TYPE_BOOL) array_push($type_stack, TYPE_BOOL);
					else typeCheckError("Can only invert the state of a boolean with OP_NOT, instead got `" . getHumanReadableTypes(array($t)) . "`", $token->getTokenInformation());
					break;
				case OP_DROP:
					if (sizeof($type_stack) < 1) typeCheckError("No elements to drop for OP_DROP", $token->getTokenInformation());
					array_pop($type_stack);
					break;
				case OP_DUP:
					if (sizeof($type_stack) < 1) typeCheckError("No elements to duplicate for OP_DUP", $token->getTokenInformation());
					$t = array_pop($type_stack);
					for ($i = 0; $i < 2; $i++) array_push($type_stack, $t);
					break;
				case OP_OVER:
					if (sizeof($type_stack) < 2) typeCheckError("Not enough elements to jump over for OP_OVER", $token->getTokenInformation());
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					array_push($type_stack, $t2);
					array_push($type_stack, $t1);
					array_push($type_stack, $t2);
					break;
				case OP_ROT:
					if (sizeof($type_stack) < 3) typeCheckError("Not enough elements to rotate for OP_ROT", $token->getTokenInformation());
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					$t3 = array_pop($type_stack);
					array_push($type_stack, $t2);
					array_push($type_stack, $t1);
					array_push($type_stack, $t3);
					break;
				case OP_SWAP:
					if (sizeof($type_stack) < 2) typeCheckError("Not enough arguments to swap for OP_SWAP", $token->getTokenInformation());
					$t1 = array_pop($type_stack);
					$t2 = array_pop($type_stack);
					array_push($type_stack, $t1);
					array_push($type_stack, $t2);
					break;
				case OP_LABEL:
					// afaik there isn't anything here, unless gotos gets added, in which case you should keep state of type stack at current state.
					break;
				case OP_JMP:
					// same as above, unless gotos are added, no need to do anything. if gotos are added, need to check if type stack at jump position and at label are identical
					break;
				default:
					echo "[ERROR]: Unhandled op_code : " . $ir->op_code . "\n";
					exit(69);
			}
		}
	}

	function getTypesFromHumanReadable($types) {
		$ret = array();
		foreach ($types as $type) {
			if ($type === "void");
			else if ($type === "int") array_push($ret, TYPE_INT);
			else if ($type === "bool") array_push($ret, TYPE_BOOL);
			else {
				echo "[ERROR]: Unhandled type : " . $type . "\n";
				exit(69);
			}
		}
		return $ret;
	}

	function getHumanReadableTypes($type_stack) {
		$ret = "";
		foreach($type_stack as $type) {
			if ($type === TYPE_VOID) $ret = $ret . " void";
			else if ($type === TYPE_INT) $ret = $ret . " int";
			else if ($type === TYPE_BOOL) $ret = $ret . " bool";
			else {
				echo "[ERROR]: Unhandled type number : " . $type . "\n";
				exit(69);
			}
		}
		if ($ret === "") $ret = " void";
		$ret = substr($ret, 1);
		return $ret;
	}

	function generate($inter_repr) {
		if (OP_COUNT != 28) {
			echo "[ERROR]: Unhandled op_code in code generation, there are now " . OP_COUNT . " op_codes\n";
			exit(127);
		}
		$current_function = null;
		$file = fopen("output.asm", "w") or die ("[ERROR]: Unable to open the output file\n");
		fwrite($file, "BITS 64\n");
		fwrite($file, "section .text\n");
		fwrite($file, "global _start\n");
		generateFromIR($inter_repr, $file);

		fwrite($file, "section .bss\n");
		fwrite($file, "\tcall_stack: resb " . CALL_STACK_SIZE ."\n");
		fclose($file);
	}

	function generateFromIR($inter_repr, $file) {
		foreach ($inter_repr as $operation) {
			switch ($operation->op_code) {
				case OP_FUNCTION:
					fwrite($file, "\t;; OP_FUNCTION\n");
					fwrite($file, $operation->value->name . ":\n");
					if ($operation->value->name !== "_start") {
						fwrite($file, "\tpop r14\n");
						fwrite($file, "\tmov [call_stack+r15*8], r14\n");
						fwrite($file, "\tinc r15\n");
					}
					else fwrite($file, "\tmov r15, 0\n");
					break;
				case OP_RETURN:
					fwrite($file, "\t;; OP_RETURN\n");
					fwrite($file, "\tdec r15\n");
					fwrite($file, "\tmov r14, [call_stack+r15*8]\n");
					fwrite($file, "\tpush r14\n");
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
				case OP_DO:
					fwrite($file, "\t;;OP_DO\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\ttest rax, rax\n");
					fwrite($file, "\tjz " . $operation->value . "\n");
					break;
				case OP_LABEL:
					fwrite($file, "\t;; OP_LABEL\n");
					fwrite($file, "\t" . $operation->value . ":\n");
					break;
				case OP_JMP:
					fwrite($file, "\t;; OP_JMP\n");
					fwrite($file, "\tjmp " . $operation->value . "\n");
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
				case OP_EQ:
					fwrite($file, "\t;; OP_EQ\n");
					fwrite($file, "\tmov rsi, 0\n");
					fwrite($file, "\tmov rdx, 1\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tpop rdi\n");
					fwrite($file, "\tcmp rax, rdi\n");
					fwrite($file, "\tcmove rsi, rdx\n");
					fwrite($file, "\tpush rsi\n");
					break;
				case OP_NOT:
					fwrite($file, "\t;; OP_NOT\n");
					fwrite($file, "\tpop rax\n");
					fwrite($file, "\tmov rdi, 1\n");
					fwrite($file, "\txor rax, rdi\n");
					fwrite($file, "\tpush rax\n");
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
					fwrite($file, "\tmov rdx, 0\n");
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
