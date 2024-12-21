math-eval - A math expression evaluator in PHP
===

`version 1.0`

**math-eval** is a lighweight and fast library to evaluate a mathematical expressions without the need to use PHP's `eval()`


&nbsp;


TL;DR
===
Example: *Two raised to the power of three plus the sine of* ***π*** *divided by eight factorial, all multiplied by ten to the power of three.*

**in your project:**

```PHP
require_once( "matheval.php" );

use function \Kalei\Matheval\matheval;

$expression = "fooBAR^3 + sin(pi/8!) * 10e3";

$parameters = [ "fooBAR" => 2 ];

$result = matheval( $expression,
                    $parameters,
                    $error );
						
if( $result !== false )
{
    printf("%.6f", $number);  // ==> 8.779165
}
else
{
    echo "$error\n;
}
```


**in the terminal:**

```
$ php matheval.php '2^3 + sin(pi/8!) * 10e3'
8.7791648438519
```

&nbsp;


Expressions syntax reference
===

**Operators**

`+` plus

`-` minus

`*` multiplication

`/` division

`^` exponentiation (*right associative as it should be*)

`!` factorial (using Gamma function)

&nbsp;


**Available functions**

`sin(r)`  sine

`cos(r)`  cosine

`tan(r)`  tangent

`asin(n)` arcsin

`acos(n)` arccos

`atan(n)` arctan

`fact(n)` factorial of `n`. Equivalent to `n!`.

`exp(n)` base **e** exponential function of `n`. Equivalent to `e^n`.

`pow(b, n)` `b` to the power of `n`. Equivalent to `b^n`.

`log(n)` natural logarithm of `n` (with base **e**)

`log(b, n)` logarithm of `n` with base `b`

`max(n1, n2, n3, ...)` maximum of one or more numbers

`min(n1, n2, n3, ...)` minimum of one or more numbers

`average(n1, n2, ...)` or `avg(n1, ...)` average of one or more numbers

&nbsp;

**Numbers can be expressed as follows:**

`0.123`  or  `.123`  or  `12.3E-2`  etc..

&nbsp;

**Recognized constants:**

`e`  euler number

`pi` **Pi**

&nbsp;

**Parentheses:**

Use round brackets `(` `)` to nest expressions.


&nbsp;

**Operators precedence:**

Highest to lowest:

`(` `)`

`!`

`^`

`-` unary minus, right associative

`*` `/`

`+` `-`

Unary minus has **lower** precedence than exponentiation (like in Python); for example:

`- 3 ^ 2` is evaluated as `- (3  ^ 2 )` = `-9`

However it is advisable to use parentheses when the expression is abiguous (ex. JS Chrome raises an arror for `-3**2`.

&nbsp;

**Whitespace:**

Whitespace, tabs and newlines are ignored.

&nbsp;

**Errors:**

If the expression is malformed or it is correct but would result in math operations not allowed (division by zero) or operations that would generate a complex number or too big number (overflow) then `false` is returned and a explicative error message is returned in `$error`.

&nbsp;

math-eval-c
=======

**math-eval-php** is a port of **[math-eval-c](https://github.com/paolobertani/math-eval-c)**

&nbsp;

Contact
=======

visit **[Kalei](https://www.kalei.it)**

&nbsp;

FreeBSD 2-clause license
========================

**Copyright (c) 2024, Paolo Bertani - Kalei S.r.l.**

**All rights reserved.**

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.














