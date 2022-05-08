# KuteForth

This is a small programming language i am developping on my free time, to experiment with different kind of features programming languages can have.


## How to use : 

```console
$ ./kuteforth.php foo.kf       # compiles the file named foo.kf
$ ./kuteforth.php -r foo.kf    # compiles and automatically run the file named foo.kf
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

## Arithmetics :

KuteForth is capable of basic arithmetics, which are :
```
plus   -> adds the two numbers on top of the stack and pushes the result onto the stack

minus  -> substracts the second number on the stack from the first and pushes the result onto the stack

mult   -> multiplies the two numbers on top of the stack and pushes the result onto the stack

divmod -> divides the second number on the stack from the first, pushes the quotient at the first place and the remainder at the first place on the stack
```

## Stack dumper :
If at one point you are confused or forget what type of elements are onto the stack, you can use the special keyword `???`, which will stop the compiler whenever encountered, and will print the state of the type stack at that position.
