<?php
/*

matheval
version 0.9

mathematical expression evaluator

Copyright (c) 2024 Paolo Bertani - Kalei S.r.l.
Licensed under the FreeBSD 2-clause license

-------------------------------------------------------------------------------

FreeBSD 2-clause license

Copyright (c) 2016-2024, Paolo Bertani - Kalei S.r.l.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS \AS IS\ AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

-------------------------------------------------------------------------------

*/



if( ! defined( "matheval_unary_minus_has_highest_precedence" ) )
{
    define( "matheval_unary_minus_has_highest_precedence", true );
}



function matheval( $expression, $parameters = null, &$error = null )
{
    $name = "";
    $value = 0.0;

    //
    // parameters check
    //

    if( ! is_string( $expression ) )
    {
        throw new Exception( "expression must be string" );
    }

    if( $parameters === null )
    {
        $parameters = [];
    }
    else
    {
        $keywords =
        [
            "e",
            "exp",
            "fact",
            "pi",
            "pow",
            "cos",
            "sin",
            "tan",
            "log",
            "max",
            "min",
            "acos",
            "asin",
            "atan",
            "average",
            "avg"
        ];

        foreach( $parameters as $name => $value )
        {
            if( ! is_string( $name ) )
            {
                throw new Exception( "parameter name must be a string" );
            }

            if( ! ctype_alnum( $name ) )
            {
                throw new Exception( "parameter name must be an alphanumeric string" );
            }

            if( ctype_digit( substr( $name, 0, 1 ) ) )
            {
                throw new Exception( "parameter name must not begin with a digit" );
            }

            if( ! is_int( $value ) && ! is_float( $value ) )
            {
                throw new Exception( "parameter value must be a integer or float" );
            }

            if( in_array( $name, $keywords ) !== false )
            {
                throw new Exception( "parameter name must not be a reserved keyword: $name" );
            }
        }

        unset( $name );
        unset( $value );
        unset( $keywords );
    }

    uksort( $parameters, function ( $key1, $key2 )
    {
        return strlen( $key2 ) - strlen( $key1 );
    } );



    //
    // main
    //

    $eval = new StdClass();
    $eval->expression = $expression . "\0";
    $eval->params = $parameters;
    $eval->cursor = 0;
    $eval->result = 0;
    $eval->roundBracketsCount = 0;
    $eval->error = "";

    $result = _processAddends( $eval, -1, true, false );

    if( $eval->error )
    {
        $error = $eval->error;
        return false;
    }
    else
    {
        $error = "";
        return $result;
    }
}



//
// private functions
//

// Evaluates a single value or expression A0 or
// sequence of 2 or more addends:
// A1 - A2 [ + A3 [ - A4 ... ] ]
// Addends can be a single values or expressions with
// higher precedence. In the second case the expression is evaluated first.
// "breakOn" parameters define cases where the function must exit.

function _processAddends( $eval,
                          $breakOnRoundBracketsCount,    // If open brackets count goes down to this count then exit;
                          $breakOnEOF,                   // exit if the end of the string is met;
                          $breakOnCOMMA,                 // exit if a comma is met;
                         &$tokenThatCausedBreak = NULL ) // if not null the token/symbol that caused the function to exit;
{
    $leftOp = NULL;
    $rightOp = NULL;

    $value = 0.0;
    $result = 0.0;

    // Let's pretend we already computed
    // 0 + ...

    $result = 0;
    $rightOp = "ETSum";

    do
    {
        $leftOp = $rightOp;

        // [ Each addend A is treated as a (potential and higher-precedence)
        //   multiplication and evaluated as 1 * A with the function below ]
        $value = _processFactors( $eval, 1, "ETMul", false, $rightOp );
        if( $eval->error ) return 0;

        $result = $leftOp === "ETSum" ? ( $result + $value ) : ( $result - $value );

        // ...and go on as long there are sums ands subs.
    }
    while( $rightOp === "ETSum" || $rightOp === "ETSum" );

    // A round close bracket:
    // check for negative count.

    if( $rightOp === "ETrbc" )
    {
        $eval->roundBracketsCount--;
        if( $eval->roundBracketsCount < 0 )
        {
            $eval->error = "unexpected close round bracket";
            return 0;
        }
    }

    // Returns the token that caused the function to exit

    $tokenThatCausedBreak = $rightOp;

    // Check if must exit

    if( ( $eval->roundBracketsCount === $breakOnRoundBracketsCount ) || ( $breakOnEOF && $rightOp === "ETEof" ) || ( $breakOnCOMMA && $rightOp === "ETcom" ) )
    {
        if( is_nan( $result ) || is_infinite( $result ) )
        {
            $eval->error = "result is complex or too big";
            return 0;
        }

        return $result;
    }

    // If not it's an error.

    switch( $rightOp )
    {
        case "ETEof":
            $eval->error = "unexpected end of expression";
            break;

        case "ETrbc":
            $eval->error = "unexpected close round bracket";
            break;

        case "ETcom":
            $eval->error = "unexpeced comma";
            break;

        default:
            $eval->error = "unexpeced symbol";
            break;
    }

    return 0;
}



// Evaluates a sequence of 1 or more multiplies or divisions
// F1 [ * F2  [ / F3 [ * F4 ... ] ] ]
// Where Fn is a value or a higher precedence expression.

function _processFactors( $eval,
                          $leftValue, // The value (already fetched) on the left to be multiplied(divided);
                          $op,        // is it multiply or divide;
                          $isExponent,// is an exponent being evaluated ?
                         &$leftOp )   // RETURN: factors are over, this is the next operator (token).
{
    $token = "";
    $nextOp = "";

    $rightValue = 0.0;
    $sign = 1;

    $functions =
    [
        "exp",
        "fact",
        "pow",
        "cos",
        "sin",
        "tan",
        "log",
        "max",
        "min",
        "acos",
        "asin",
        "atan",
        "average",
        "avg"
    ];

    do
    {
        $rightValue = _processToken( $eval, $token );
        if( $eval->error ) return 0;

        // Unary minus or plus ?
        // store the sign and get the next token

        if( $token === "ETSub" )
        {
            $sign = -1;
            $rightValue = _processToken( $eval, $token );
            if( $eval->error ) return 0;
        }
        else if( $token === "ETSum" )
        {
            $sign = 1;
            $rightValue = _processToken( $eval, $token );
            if( $eval->error ) return 0;
        }
        else
        {
            $sign = 1;
        }

        // Open round bracket?
        // The expression between brackets is evaluated.

        if( $token === "ETrbo" )
        {
            $eval->roundBracketsCount++;

            $rightValue = _processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;

            $token = "value";
        }

        // A function ?

        if( in_array( $token, $functions ) )
        {
            $rightValue = _processFunction( $eval, $token );
            if( $eval->error ) return 0;

            $token = "ETVal";
        }

        // Excluded previous cases then
        // the token must be a number.

        if( $token !== "ETVal" )
        {
            $eval->error = "expected value";
            return 0;
        }

        // Get beforehand the next token
        // to see if it's an exponential or factorial operator

        _processToken( $eval, $nextOp );
        if( $eval->error ) return 0;

        // Unary minus precedence (highest/lowest) affects this section of code

        if( $nextOp === "ETFct" )
        {
            if( matheval_unary_minus_has_highest_precedence )
            {
                $rightValue = _processFactorial( $eval, $rightValue * $sign, $nextOp );
                $sign = 1;
            }
            else
            {
                $rightValue = _processFactorial( $eval, $rightValue, $nextOp );
            }
            if( $eval->error ) return 0;
        }

        if( $nextOp === "ETExc" )
        {
            if( matheval_unary_minus_has_highest_precedence )
            {
                $rightValue = _processExponentiation( $eval, $rightValue * $sign, $nextOp );
                $sign = 1;
            }
            else
            {
                $rightValue = _processExponentiation( $eval, $rightValue, $nextOp );
            }
            if( $eval->error ) return 0;
        }

        // multiplication/division is finally
        // calculated

        if( $op === "ETMul" )
        {
            $leftValue = $leftValue * $rightValue * $sign;
        }
        else
        {
            if( $rightValue === 0 )
            {
                $eval->error = "division by zero";
                return 0;
            }
            $leftValue = $leftValue / $rightValue * $sign;
        }

        if( is_infinite( $leftValue ) )
        {
            $eval->error = "result is too big";
            return 0;
        }

        // The next operator has already been fetched.

        $op = $nextOp;

        // Go on as long multiply or division operators are met...
        // ...unless an exponent is evaluated
        // (because exponentiation ^ operator have higher precedence)
    }
    while( ( $op === "ETMul" || $op === "ETDiv" ) && ! $isExponent );

    $leftOp = $op;

    return $leftValue;
}



// Evaluates the expession(s) (comma separated if multiple)
// inside the round brackets then computes the function
// specified by the token `func`.

function _processFunction( $eval, $func )
{
    $result = 0.0;
    $result2= 0.0;

    $count = 0;

    $tokenThatCausedBreak = "";
    $token = "";

    // Eat an open round bracket and count it

    processToken( $eval, $token );
    if( $eval->error ) return 0;

    if( $token !== "ETrbo" )
    {
        $eval->error = "expected open round bracket after function name";
        return 0;
    }

    $eval->roundBracketsCount++;

    switch( $func )
    {
        case "ETSin":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            $result = sin( $result );
            break;

        case "ETCos":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            $result = cos( $result );
            break;

        case "ETTan":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            $result = tan( $result );
            break;

        case "ETASi":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            $result = asin( $result );
            break;

        case "ETACo":
            $result = processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            $result = acos( $result );
            break;

        case "ETATa":
            $result = processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            $result = atan( $result );
            break;

        case "ETFac":
            $result = processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            if( $result < 0 )
            {
                $eval->error = "attempt to evaluate factorial of negative number";
            }
            else
            {

                $result = _gamma( 1 + $result );
            }
            break;

        case "ETExp":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            $result = exp( $result );
            break;

        case "ETPow":
            $result = _processAddends( $eval, -1, false, true, NULL );
            if( $eval->error ) return 0;
            $result2 = _processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
            if( $eval->error ) return 0;
            $result = pow( $result, $result2 );
            break;

        case "ETLog":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, true, $tokenThatCausedBreak );
            if( $eval->error ) return 0;
            if( $tokenThatCausedBreak === "ETrbc" )
            {
                // log(n) with one parameter
                $result = log( $result );
            }
            else
            {
                $result2 = _processAddends( $eval, $eval->roundBracketsCount - 1, false, false, NULL );
                if( $eval->error ) return 0;
                $result = log( $result2 ) / log( $result );
            }
            break;

        case "ETMax":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, true, $tokenThatCausedBreak );
            if( $eval->error ) return 0;
            while( $tokenThatCausedBreak === "ETcom" )
            {
                $result2 = _processAddends( $eval, $eval->roundBracketsCount - 1, false, true, $tokenThatCausedBreak );
                if( $eval->error ) return 0;

                if( $result2 > $result )
                {
                    $result = $result2;
                }
            }
            break;

        case "ETMin":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, true, $tokenThatCausedBreak );
            if( $eval->error ) return 0;
            while( $tokenThatCausedBreak === "ETcom" )
            {
                $result2 = _processAddends( $eval, $eval->roundBracketsCount - 1, false, true, $tokenThatCausedBreak );
                if( $eval->error ) return 0;

                if( $result2 < $result )
                {
                    $esult = $result2;
                }
            }
            break;

        case "ETAvg":
            $result = _processAddends( $eval, $eval->roundBracketsCount - 1, false, true, $tokenThatCausedBreak );
            if( $eval->error ) return 0;
            $count = 1;
            while( $tokenThatCausedBreak === "ETcom" )
            {
                $result2 = _processAddends( $eval, $eval->roundBracketsCount - 1, false, true, $tokenThatCausedBreak );
                if( $eval->error ) return 0;

                $result += $result2;
                $count++;
            }
            $result = $result / $count;
            break;

        default:
            $result = 0;
            break;
    }

    if( is_nan( $result ) || is_infinite( $result ) )
    {
        $eval->error = "result is complex or too big";
        return 0;
    }

    return $result;
}



// Evaluates an exponentiation.

function _processExponentiation( $eval,
                                 $base,      // The base has already been fetched;
                                &$rightOp )  // RETURN: the token (operator) that follows.
{
    $exponent = 0.0;
    $result = 0.0;

    $exponent = _processFactors( $eval, 1, "ETMul", true, $rightOp );
    if( $eval->error ) return 0;

    $result = pow( $base, $exponent );
    if( is_nan( $result ) || is_infinite( $result ) )
    {
        $eval->error = "result is complex or too big";
        return 0;
    }

    return $result;
}



// Evaluates a factorial using the Gamma function.

function _processFactorial( $eval,
                            $value,     // The value to compute has already been fetched;
                           &$rightOp )  // RETURN: the token (operator) that follows.
{
    $result = 0.0;

    if( $value < 0 )
    {
        $eval->error = "attempt to evaluate factorial of negative number";
        $rightOp = "ETErr";
        return 0;
    }

    $result = _gamma( value + 1 );

    if( is_nan( $result ) || is_infinite( $result ) )
    {
        $eval->error = "result is complex or too big";
        return 0;
    }

    _processToken( $eval, $rightOp );
    if( $eval->error ) return 0;

    return $result;
}



// Parses the next token and advances the cursor.
// The function returns a number if the token is a value a const. or a param.
// Whitespace is ignored.

function _processToken( $eval,
                       &$token ) // RETURN: the token.
{
    $t = "";
    $v = 0.0;
    $name = "";
    $value = 0.0;

    $t = "ETBlk";
    $v = 0;

    while( $t === "ETBlk" )
    {
        // value maybe

        if( ( ($eval->expression)[$eval->cursor] >= "0" && ($eval->expression)[$eval->cursor] <= "9" ) || ($eval->expression)[$eval->cursor] === "." )
        {
            $v = _processValue( $eval );
            if( $eval->error )
            {
                $t = "ETErr";
                return $t;
            }
            else
            {
                $t = "ETVal";
            }
            break;
        }
        else
        {
            // parameter maybe

            foreach( $eval->params as $name => $value )
            {
                if( substr( $eval->expression, $eval->cursor, strlen( $name ) ) === $name )
                {
                    $token = "ETVal";
                    $eval->cursor += strlen( $name );
                    return $value;
                }
            }

            // token maybe

            switch( ($eval->expression)[$eval->cursor] )
            {
                case "\n":
                case "\r":
                case "\t":
                case " ":
                    $t = "ETBlk";
                    $eval->cursor++;
                    break;

                case "+":
                    _processPlusToken( $eval, $t);
                    break;

                case "-":
                    $t = "ETSub";
                    $eval->cursor++;
                    break;

                case "*":
                    $t = "ETMul";
                    $eval->cursor++;
                    break;

                case "/":
                    $t = "ETDiv";
                    $eval->cursor++;
                    break;

                case "^":
                    $t = "ETExc";
                    $eval->cursor++;
                    break;

                case "!":
                    $t = "ETFct";
                    $eval->cursor++;
                    break;

                case "(":
                    $t = "ETrbo";
                    $eval->cursor++;
                    break;

                case ")":
                    $t = "ETrbc";
                    $eval->cursor++;
                    break;

                case "\0":
                    $t = "ETEof";
                    $eval->cursor++;
                    break;

                case ",":
                    $t = "ETcom";
                    $eval->cursor++;
                    break;

                case "e":
                    if( substr( $eval->expression, $eval->cursor, 3 ) === "exp" )
                    {
                        $t = "ETExp";
                        $eval->cursor += 3;
                    }
                    else
                    {
                        $v = exp( 1 );
                        $t = "ETVal";
                        $eval->cursor++;
                    }
                    break;

                case "f":
                    if( substr( $eval->expression, $eval->cursor, 4 ) === "fact" )
                    {
                        $t = "ETFac";
                        $eval->cursor += 4;
                    }
                    else
                    {
                        $t = "ETErr";
                    }
                    break;

                case "p":
                    if( substr( $eval->expression, $eval->cursor, 2 ) === "pi" )
                    {
                        $v = M_PI;
                        $t = "ETVal";
                        $eval->cursor += 2;
                    }
                    elseif( substr( $eval->expression, $eval->cursor, 3 ) === "pow" )
                    {
                        $t = "ETPow";
                        $eval->cursor += 3;
                    }
                    else
                    {
                        $t = "ETErr";
                    }
                    break;

                case "c":
                    if( substr( $eval->expression, $eval->cursor, 3 ) === "cos" )
                    {
                        $t = "ETCos";
                        $eval->cursor += 3;
                    }
                    else
                    {
                        $t = "ETErr";
                    }
                    break;

                case "s":
                    if( substr( $eval->expression, $eval->cursor, 3 ) === "sin" )
                    {
                        $t = "ETSin";
                        $eval->cursor += 3;
                    }
                    else
                    {
                        $t = "ETErr";
                    }
                    break;

                case "t":
                    if( substr( $eval->expression, $eval->cursor, 3 ) === "tan" )
                    {
                        $t = "ETTan";
                        $eval->cursor += 3;
                    }
                    else
                    {
                        $t = "ETErr";
                    }
                    break;

                case "l":
                    if( substr( $eval->expression, $eval->cursor, 3 ) === "log" )
                    {
                        $t = "ETLog";
                        $eval->cursor += 3;
                    }
                    else
                    {
                        $t = "ETErr";
                    }
                    break;

                case "m":
                    if( substr( $eval->expression, $eval->cursor, 3 ) === "max" )
                    {
                        $t = "ETMax";
                        $eval->cursor += 3;
                    }
                    elseif( substr( $eval->expression, $eval->cursor, 3 ) === "min" )
                    {
                        $t = "ETMin";
                        $eval->cursor += 3;
                    }
                    else
                    {
                        $t = "ETErr";
                    }
                    break;

                case "a":
                    if( substr( $eval->expression, $eval->cursor, 4 ) === "asin" )
                    {
                        $t = "ETASi";
                        $eval->cursor += 4;
                    }
                    elseif( substr( $eval->expression, $eval->cursor, 4 ) === "acos" )
                    {
                        $t = "ETACo";
                        $eval->cursor += 4;
                    }
                    elseif( substr( $eval->expression, $eval->cursor, 4 ) === "atan" )
                    {
                        $t = "ETATa";
                        $eval->cursor += 4;
                    }
                    elseif( substr( $eval->expression, $eval->cursor, 7 ) === "average" )
                    {
                        $t = "ETAvg";
                        $eval->cursor += 7;
                    }
                    elseif( substr( $eval->expression, $eval->cursor, 3 ) === "avg" )
                    {
                        $t = "ETAvg";
                        $eval->cursor += 3;
                    }
                    else
                    {
                        $t = "ETErr";
                    }
                    break;


                default:
                    $t = "ETErr";
                    break;
            }
        }
    }

    if( $t === "ETErr" )
    {
        $eval->error = "unexpected symbol";
    }

    $token = $t;

    return $v;
}



// Parses what follows an (already fetched) plus token
// ensuring that two consecutive plus are not present.
// Expressions such as 2++2 (binary plus
// followed by unitary plus) are not allowed.
// Advances the cursor.
// Always returns 0.

function _processPlusToken( $eval, &$token )
{
    $c = "";

    do
    {
        $eval->cursor++;
        $c =  ($eval->expression)[$eval->cursor];
    } while( $c === " " || $c === "\n" || $c === "\r" || $c === "\t" );

    if( $c === "+" )
    {
        $token = "ETErr";
    }
    else
    {
        $token = "ETSum";
    }

    return 0;
}



// Parses a number and advances the cursor.
// The cursor is positioned after an eventually
// `+` or `-` operator that comes before the value.

function _processValue( $eval )
{
    $endptr = 0;
    $value = 0.0;

    $value = _strtod( substr( $eval->expression, $eval->cursor ), $endptr );

    if( $endptr === 0 )
    {
        $eval->error = "expected value";
        $value = 0;
    }
    else
    {
        $eval->cursor += $endptr;

        if( is_nan( $value ) || is_infinite( $value ) )
        {
            $eval->error = "value is too big";
            return 0;
        }
    }

    return $value;
}



// php implementation of C's strtod

function _strtod( $string, &$endptr = null )
{
    $string = ltrim( $string );

    // Match a valid floating-point number

    if( preg_match('/^[+-]?([0-9]*\.[0-9]+|[0-9]+(\.[0-9]*)?)([eE][+-]?[0-9]+)?/', $string, $matches ) )
    {
        $number = $matches[0];

        $result = (float) $number;

        $endptr = strlen( $number );

        return $result;
    }

    $endptr = 0;

    return 0.0;
}



// php implementation gamma function
// https://hewgill.com/picomath/php/gamma.php.html

function _gamma( $x )
{
    # Split the function domain into three intervals:
    # ( 0, 0.001 ), [0.001, 12 ), and ( 12, infinity )

    ###########################################################################
    # First interval: ( 0, 0.001 )
    #
    # For small x, 1/_gamma( x ) has power series x + _gamma x^2  - ...
    # So in this range, 1/_gamma( x ) = x + _gamma x^2 with error on the order of x^3.
    # The relative error over this interval is less than 6e-7.

    $gamma = 0.577215664901532860606512090; # Euler's _gamma constant

    if( $x < 0.001 )
    {
        return 1.0/( $x*( 1.0 + $gamma*$x ) );
    }

    ###########################################################################
    # Second interval: [0.001, 12 )

    if( $x < 12.0 )
    {
        # The algorithm directly approximates _gamma over ( 1,2 ) and uses
        # reduction identities to reduce other arguments to this interval.

        $y = $x;
        $n = 0;
        $arg_was_less_than_one = ( $y < 1.0 );

        # Add or subtract integers as necessary to bring y into ( 1,2 )
        # Will correct for this below
        if( $arg_was_less_than_one )
        {
            $y += 1.0;
        }
        else
        {
            $n = floor( $y ) - 1;  # will use n later
            $y -= $n;
        }

        # numerator coefficients for approximation over the interval ( 1,2 )
        $p =
        [
            -1.71618513886549492533811E+0,
             2.47656508055759199108314E+1,
            -3.79804256470945635097577E+2,
             6.29331155312818442661052E+2,
             8.66966202790413211295064E+2,
            -3.14512729688483675254357E+4,
            -3.61444134186911729807069E+4,
             6.64561438202405440627855E+4
        ];

        # denominator coefficients for approximation over the interval ( 1,2 )
        $q =
        [
            -3.08402300119738975254353E+1,
             3.15350626979604161529144E+2,
            -1.01515636749021914166146E+3,
            -3.10777167157231109440444E+3,
             2.25381184209801510330112E+4,
             4.75584627752788110767815E+3,
            -1.34659959864969306392456E+5,
            -1.15132259675553483497211E+5
        ];

        $num = 0.0;
        $den = 1.0;

        $z = $y - 1;
        for ( $i = 0; $i < 8; $i++ )
        {
            $num = ( $num + $p[$i] )*$z;
            $den = $den*$z + $q[$i];
        }
        $result = $num/$den + 1.0;

        # Apply correction if argument was not initially in ( 1,2 )
        if( $arg_was_less_than_one )
        {
            # Use identity _gamma( z ) = _gamma( z+1 )/z
            # The variable "result" now holds _gamma of the original y + 1
            # Thus we use y-1 to get back the orginal y.
            $result /= ( $y-1.0 );
        }
        else
        {
            # Use the identity _gamma( z+n ) = z*( z+1 )* ... *( z+n-1 )*_gamma( z )
            for ( $i = 0; $i < $n; $i++ ) {
                $result *= $y++;
            }
        }

        return $result;
    }

    ###########################################################################
    # Third interval: [12, infinity )

    if( $x > 171.624 )
    {
        # Correct answer too large to display.
        return Double.POSITIVE_INFINITY;
    }

    return exp( _logGamma( $x ) );
}



function _logGamma( $x )
{
    if( $x < 12.0 )
    {
        return log( abs( _gamma( $x ) ) );
    }

    # Abramowitz and Stegun 6.1.41
    # Asymptotic series should be good to at least 11 or 12 figures
    # For error analysis, see Whittiker and Watson
    # A Course in Modern Analysis ( 1927 ), page 252

    $c = [
         1.0/12.0,
        -1.0/360.0,
         1.0/1260.0,
        -1.0/1680.0,
         1.0/1188.0,
        -691.0/360360.0,
         1.0/156.0,
        -3617.0/122400.0
    ];
    $z = 1.0/( $x*$x );
    $sum = $c[7];
    for ( $i=6; $i >= 0; $i-- )
    {
        $sum *= $z;
        $sum += $c[$i];
    }
    $series = $sum/$x;

    $halfLogTwoPi = 0.91893853320467274178032973640562;
    $log_gamma = ( $x - 0.5 )*log( $x ) - $x + $halfLogTwoPi + $series;
    return $log_gamma;
}
