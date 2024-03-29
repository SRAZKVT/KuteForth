# KuteForth

## IMPORTANT :
### This language is currently archived, and served as an experiment on writing a basic language. Although this language was small, we found several problems in it and how they could be solved.

- The compiler backend in this language was completely custom, using the nasm assembler. Nasm is very nice if you are writing assembly by hand, however, it isn't great in case of machine generated assembly. Due to using nasm, debug information isn't available, and we cannot use tools such as gdb or lldb, unless we are working on the assembly itself.
- The stack dumper is helpful in case we are stuck. However, extra type information (just a kind of comment attached to the type itself) would be useful, as cases where we have 3 pointers and need to manipulate them aren't uncommon, and aren't easy to deal with either with minimum information
- Compile time evaluation would be useful, here specifically for example for memory plages. Since the memory plage is defined at compile time, the size also needs to be evaluated at compile time.
- Ability to call to the c library would free developpers from reimplementing everything, and opens the door to other operating systems, as currently, of the main operating systems (Windows, MacOS, Linux, FreeBSD, OpenBSD), only Linux allows syscalls outside of the c library
- While constants aren't an absolute necessity, they are definitely a nice feature to have
- Imports by including a file aren't ideal, as they are prone to breakage in case of circular dependencies.
- Custom type structures need to currently have size and layout memorized, or checked whenever reused, which is highly inconvenient by itself. We could call from labels on a structure, and the compiler does the pointer arithmetic for us.
- Custom types would be useful as well (for example, file descriptors could be a different type, which would make working with files a bit easier), as well as making the stack dumper more useful as it would have more information.

## Original README :

This is a small programming language i am developping on my free time, to experiment with different kind of features programming languages can have.


## How to use : 

```console
$ ./kuteforth.php foo.kf       # compiles the file named foo.kf
$ ./kuteforth.php -r foo.kf    # compiles and automatically run the file named foo.kf
$ ./kuteforth.php -d foo.kf    # prints to the terminal the intermediary representation of the program in the file named foo.kf
```
# How to use the language :

## Functions :
```
func    // define a function block
foo     // define the name of the function
void    // input parameters of the function
--      // delimiter between input and output parameters
int int // output parameters of the function
end     // closes the function block

func bar void -- void in
    foo // calls foo. calling it is possible since it has been defined, you don't need the function implementation to be before any other function that calls it
    drop drop // drop the two numbers returned by foo
end

func foo void -- int int in // the function foo has been defined already, but no implementation was given at the time, it is provided here
    // foo do things
    1 2 // this just leaves the two numbers on the stack, they will be available for the function that called them, in this case, bar
end
```


## Stack manipulation :

The basic stack manipulation operators are the sames as Forth's:
```
drop
A B -> A
A   ->

dup
A   -> A A

swap
A B -> B A

over
A B -> A B A

rot
A B C -> B C A
```

There are also new stack manipulation operations available:
```
save
A B C -> C A B C
```

## Arithmetics :

KuteForth is capable of basic arithmetics, which are :
```
plus   -> adds the two numbers on top of the stack and pushes the result onto the stack

minus  -> substracts the second number on the stack from the first and pushes the result onto the stack

mult   -> multiplies the two numbers on top of the stack and pushes the result onto the stack

divmod -> divides the second number on the stack from the first, pushes the quotient at the first place and the remainder at the first place on the stack

andi   -> realise a bitwise and on the two first elements on the stack, and pushes the result

ori    -> realise a bitwise or  on the two first elements on the stack, and pushes the result

xori   -> realise a bitwise xor on the two first elements on the stack, and pushes the result

noti   -> realise a bitwise not on the two first elements on the stack, and pushes the result
```

## Conditions :

There are currently 3 conditional blocks : if, else and elif. They are used as follows :
```
if <condition> do
	// things
elif <condition> do
	// things
else
	// things
end
```

## Loops:
There is currently only 1 type of loops, a while loop. It is used as follows :
```
while <condition> do
	// things
end
```
The condition will be reevaluated at each pass, and whenever said condition returns false, then the loop will stop.

## Memory:
There is currently a static buffer into the compiled programs, of 64kB, of which you can access the begining with `mem_start`. mem_start pushes at the top of the stack a pointer to the begining of that buffer. You can write at a pointer using `<ptr> <value> pwrite`, as well as reading from a pointer with `<ptr> pread`.
Note : Keywords `pread32`, `pread16`, `pread8`, `pwrite32`, `pwrite16` and  `pwrite8` are 32, 16 and 8 bits versions of the `pread` and `pwrite` operations

It is also possible to create global memory statically, outside of the static buffer, by using `<size in bytes> memory`. For example, `memory 8` will create a 64 bits long memory segment.

## Arguments:
Command line arguments are accessible through the keywords `argc` and `argv`. `argc` will push on the stack the amount of arguments given, while argv will consume one integer serving as index of the argument, and push on the stack a pointer to the begining of that argument (for example, `1 argv` will push pointer to the executable invoked)

## Stack dumper :
If at one point you are confused or forget what type of elements are onto the stack, you can use the special keyword `???`, which will stop the compiler whenever encountered, and will print the state of the type stack at that position.
