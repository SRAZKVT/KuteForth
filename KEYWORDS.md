## This is a list of all keywords, and what the effect of each is.

`func` -- opens a function definition context, which contains a name for said function, types accepted as the output, and types returned.
`include` -- opens an inclusion block. Any string within that block will be interpreted as a file name to include, or if it starts with neither / or ./, as a file in the standard library.
`in` -- opens a code block.
`end` -- closes the current block.
`if` -- opens a conditional block for an if block.
`do` -- closes a conditional block, consumes a boolean, and jumps at its end if the boolean is evaluated as false.
`else` -- closes a do block, if this keyword is hit from a do block, then jump to end of the else block.
`elif` -- like an else, except it also opens a conditional block.
`while` -- opens a conditional block. At the end of a do block linked to a while, jump to the begining of the while conditional block, to reevaluate the condition.
`syscall0` -- effectuates a syscall, consumes 1 argument, serving as opcode.
`syscall1` -- effectuates a syscall, consumes 2 argument, first one serving as opcode.
`syscall2` -- effectuates a syscall, consumes 3 argument, first one serving as opcode.
`syscall3` -- effectuates a syscall, consumes 4 argument, first one serving as opcode.
`syscall4` -- effectuates a syscall, consumes 5 argument, first one serving as opcode.
`syscall5` -- effectuates a syscall, consumes 6 argument, first one serving as opcode.
`syscall6` -- effectuates a syscall, consumes 7 argument, first one serving as opcode.
`plus` -- adds the two first elements on the stack, and pushes the result.
`minus` -- substracts the first element on the stack from the second, and pushes the result.
`mult` -- multiplies the first two elements on the stack, and pushes the result.
`divmod` -- divides the first element on the stack from the second, and pushes first the quotient, followed by the remainder.
`andi` -- evaluates a binary and operation on the first two elements on the stack, and pushes the result.
`ori` -- evaluates a binary or operation on the first two elements on the stack, and pushes the result.
`xori` -- evaluates a binary xor operation on the first two elements on the stack, and pushes the result.
`noti` -- inverts all bits in the first element on the stack, and pushes the result.
`eq` -- evaluates if the two first elements on the stack are equal.
`lt` -- evaluates if the second element on the stack is strictly smaller than the first.
`gt` -- evaluates if the second element on the stack is strictly greater than the first.
`not` -- inverts a boolean argument.
`or` -- evaluates a boolean or operation on the first two elements on the stack.
`and` -- evaluates a boolean and operation on the first two elements on the stack.
`drop` -- discards the first element on the stack.
`dup` -- duplicates the first element on the stack.
`swap` -- swaps the first two elements on the stack.
`rot` -- moves the third element on the stack to the first position.
`save` -- duplicates the first element on the stack after the third element on the stack.
`over` -- duplicates the second element on the stack.
`???` -- dumps the state of the type stack, and stops compilation.
`sizeof` -- pushes as an integer the size of the type given before.
`memory` -- takes an integer, creates a static memory, and pushes a pointer to that memory.
`pwrite` -- writes at the designated pointer the 64 msot significant bits given as argument.
`pwrite32` -- same as pwrite, but writes 32 bits.
`pwrite16` -- same as pwrite, but writes 16 bits.
`pwrite8` -- same as pwrite, but writes 8 bits.
`pread` -- reads the 64 most significant bits from the designated bits.
`pread32` -- same as pread, but reads 32 bits.
`pread16` -- same as pread, but reads 16 bits.
`pread8` -- same as pread, but reads 8 bits.
`argc` -- pushes an integer which contains the amount of arguments given to the program.
`argv` -- consumes one integer, and pushes onto the stack the nth argument, as a c string (aka null terminated).
`here` -- pushes a string representation of the position of the keyword.
