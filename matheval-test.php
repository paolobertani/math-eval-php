<?php
//
//  test suite
//



require_once( "matheval.php" );



define( "MathEvaluationSuccess", 'success' );
define( "MathEvaluationFailure", 'failure' );



MathEvalRunTests();



//
// Execute all tests.
//

function MathEvalRunTests()
{
    $b = 0.0;
    $e = 0.0;
    $r = 0.0;

    $fails = 0;

    // Plus and minus (unary/binary) mixing cases

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2,       "+2" );         // plus as unary operator
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,       "2+-2" );       // plus as binary operator, minus as unary: 2 + ( -2 )
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,       "2-+2" );       // vice-versa: 2 - ( -2 )
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 4,       "2--2" );       // minus as both binary and unary operator 2 - ( -2 )
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,       "+2-(+2)" );    // leading plus
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 6,       "+2*(+3)" );    //
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -3,      "1*-3" );       //
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 6,       "2*+3" );       //
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "-+3" );        // *
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "+-3" );        // *
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "2++2" );       // * two plus as consecutive binary and unary operators not allowed
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "2---2" );      // * three minus ? not allowed
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "--2" );        // * beginning with two minus ? no, a value is expected

    // Single numbers

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2,       "2" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2,       "02" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, .2,      ".2" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -.2,     "-.2" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1234,    "1234" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 12.34,   "12.34" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1200,    "12E2" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0.12,    "12E-2" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 12,      "12E0" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "12a0" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "12.e" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "12e+" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "1.+1" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "12.e" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "12.e+" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "12.e1" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "12E2.5");      // * decimal exponent not allowed
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       ".-2" );        // * not a number

    // Round brackets

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1,       "(1)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 42,      "1+(2*(3+(4+5+6))-1)+6" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1,       "(((((((((((1)))))))))))" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -1,      "-(((((((((((1)))))))))))" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1,       "+(((((((((((1)))))))))))" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -1,      "+(((((((((((-1)))))))))))" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1,       "-(((((((((((-1)))))))))))" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "+2*(+-3)" );                   // *
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "1+(2*(3+(4+5+6))-1+6" );       // * missing close bracket
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "1+(2*(3+(4+5+6))-1))+6" );     // * too many close brackets
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "1+()" );                       // * empty expression
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       ".(((((((((((1)))))))))))" );   // *

    // Constants

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -M_PI,   "-pi" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, exp(1),  "e" );

    // Functions

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, pow(6,5),        "pow(6,5)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, exp(2.5),        "exp(2.5)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, log(3)/log(2),   "log(2,3)" );   // base is the first parameter
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, log(3),          "log(e,3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, log(4),          "log(4)" );     // log with one parameter (base e)
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, sin(M_PI*.3),    "sin(pi*.3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, cos(M_PI*.3),    "cos(pi*.3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, tan(M_PI*.3),    "tan(pi*.3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, asin(.123),      "asin(.123)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, acos(.123),      "acos(.123)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, atan(.123),      "atan(.123)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 3,       "max(-1,2,3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -1,      "min(-1,2,3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2,       "average(1,2,3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 20,      "avg(10,20,30)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 3,       "max(3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -1,      "min(-1)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2,       "average(2)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 6.2,     "avg(6.2)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "pow()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "exp()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "log()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "sin()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "cos()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "tan()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "asin()" );     // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "acos()" );     // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "atan()" );     // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "max()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "min()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "average()" );  // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "avg()" );      // * empty function
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "pow(1,2,3)" ); // * too many parameters
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "exp(1,2,3)" ); // * too many parameters
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "log(1,2,3)" ); // * too many parameters
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "sin(4,5)" );   // * too many parameters
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "cos(6,7)" );   // * too many parameters
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "tan(8,9)" );   // * too many parameters
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "asin(10,0)" ); // * too many parameters
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "acos(1,2)" );  // * too many parameters
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "atan(3,4)" );  // * too many parameters

    // Factorial

    $r = matheval_gamma(1+3.456); // as `gamma` in not present in php matheval implementation is used

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 24,      "4!" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 24,      "+4!" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1,       "0!" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, $r,      "3.456!");      // gamma function
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -24,     "-(4!)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 24,      "fact(4)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1,       "fact(0)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, $r,      "fact(3.456)");
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -24,     "-fact(4)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "(-4)!" );      // * factorial of negative number
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "!" );          // *
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "fact(-4)" );   // * factorial of negative number
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "fact()" );     // *
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,       "fact(1,2)" );  // *

    // Exponentiation

    $b = 2;
    $e = 1;
    $e = -$e / 3;
    $r = pow($b,$e);

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 8,               "2^3" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, pow(2,3.2),      "2^3.2" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, pow(2,81),       "2^3^4" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -27,             "(-3)^3" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, $r,              "2^(-1/3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0.5/3,           "2^-1/3" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, .5/3,            "(2^-1)/3" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0.5/3+1,         "2^-1/3+1" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -pow(2,-0.5),    "-1*2^(-1/2)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,               "^3" );             // *
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,               "3^" );             // *
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,               "^" );              // *

    // Equivalent forms

    $b = exp(1);
    $e = 3.5;
    $r = pow($b,$e)-exp(3.5);

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,   "e        -  exp(1)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, $r,  "e^3.5    -  exp(3.5)" );   // result slightly different from 0 due to double internal representation
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,   "log(3.2) -  log(e,3.2)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,   "1.234!   -  fact(1.234)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,   "1.2^3.4  -  pow(1.2,3.4)" );

    // Operator precedencedence

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 14,  "2+3*4" );  // + < *
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 19,  "1+2*3^2" );// + < * < ^
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 10,  "1+3^2" );  //
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 15,  "2+3*4+1" );//
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 20,  "1+2*3^2+1");
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 11,  "1+3^2+1" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 24,  "2^3*3" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 64,  "2^3!" );   // ^ < !
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -6,  "2*-3" );   // unary minus > *
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -1.5,"3/-2" );   // unary minus > /
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess,1/9.0,"3^-2" );   // unary minus > ^

    // Unary minus precedence

    if( matheval_unary_minus_has_highest_precedence )
    {
        // Unary minus has highest precedence
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess,  9,  "-3^2" );   // (-3)^2
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, .25, "2^-2" );   //
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess,  9,  "5+-2^2" ); // unary minus has always highest precedence
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess,  4,  "-2^2" );   //
        $fails += MathEvalTest( __LINE__, MathEvaluationFailure,  0,  "-3!" );    // * (-3)!
    }
    else
    {
        // Unary minus has lowest precedence (with exceptions)
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -9,  "-3^2" );   // -(3^2)
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, .25, "2^-2" );   // to make sense unary minus has highest precedence after a binary operator but...
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess,  1,  "5+-2^2" ); // ...has lowest precedence after `+`
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -4,  "-2^2" );   // -(2^.5)
        $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -6,  "-3!" );    // -(3!)
    }

    // Whitespace (with some of the above)

    $b = 2;
    $e = 1;
    $e = -$e / 3;
    $r = pow($b,$e);

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2,           "  +  2  " );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,           "2+ - 2" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0,           "2- +2" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 42,          "1+\t(2*(3 +\n\n( 4 +5+6) )-1)+6" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1,           "((((((  ((( (( 1)))  ))) ))) ))" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -1,          "  -  ((( (((( (((( 1)))))))))))" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, asin(.123),  "asin   (.123  )" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, acos(.123),  "acos(  .123)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, atan(.123),  "atan(.123  )" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 3,           "max  (-1,  2,3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -1,          "   min(-1,2 ,3   ) " );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2,           "average  (1, 2, 3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 24,          "4  !" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 1,           "  0 ! " );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, -24,         "-( 4 !)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, $r,          "  2  ^(  -1 / 3)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0.5/3,       " 2 ^ -1 / 3" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, .5/3,        "(2 ^ -1 \n\n) / 3" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0.5/3+1,     "2^-1/3+1" );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,           "2+  +2" );   // *

    // Complicate expressions - tested with http://developer.wolframalpha.com/widgetbuilder/

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0.999449080234467150824,    ".2^sin(log(e,3)*4*pi/8!)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2.417851639229258349412E24, "2^3^4-sin((pi*4!)/0.333)" );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 2.940653537774626349957,    "log(6,atan((pi*4!)/0.333)*123.987)" );

    // Common exceptions (always catched, always raise error)

    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,   "1/0" );    // * division by zero
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0,   "(-1)!" );  // * negative factorial

    // complex & overflow

    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "(-2)^(-1/2)" );                          // * complex
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "(-3)^3.5" );                             // * complex
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "pow(-2,-1/2)");                          // * complex
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "(-2)^0.5" );                             // * complex
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "log(-6,atan((pi*4!)/0.333)*123.987)" );  // * complex
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "9^9^9" );                                // * huge
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "-(9^9^9)" );                             // * huge
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "average(-9^9^9,9^9^9" );                 // * huge
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "max(-(9^9^9),9^9^9" );                   // * huge
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "min(-(9^9^9),9^9^9" );                   // * huge
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, "pow(9,pow(9,9))" );                      // * huge

    // Parameters

    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 5, " 2* foo - bar", [ "foo" => 3, "bar" => 1] );
    $fails += MathEvalTest( __LINE__, MathEvaluationFailure, 0, " 2* foo - BAZ", [ "foo" => 3, "bar" => 1] );
    $fails += MathEvalTest( __LINE__, MathEvaluationSuccess, 0.999449080234467150824,
                                                                "p1^sin(log(p2,p3)*p6*p4/p5!)",
                                                                [ "p1" => .2, "p2" => exp(1), "p3" => 3.0, "p4" => M_PI, "p5" => 8.0, "p6" => 4.0 ] );


    // Outcome

    if( $fails === 0 )
    {
        echo "All tests passed\n";
    }
    else
    {
        echo "$fails tests failed\n";
    }
}



//
// Test function: compare expected exit status (success/error) and expected result.
//

function MathEvalTest( $lineNumber, $expectedStatus, $expectedResult, $expression, $params = null )
{
    $result = 0.0;
    $error  = "";
    $exitStatus = "";

    $result = matheval( $expression, $error, $params );

    if( $result === false )
    {
        $exitStatus = MathEvaluationFailure;
        $result  = 0;
    }
    else
    {
        $exitStatus = MathEvaluationSuccess;
    }

    if( $exitStatus === $expectedStatus && floatval( $result ) === floatval( $expectedResult ) )
    {
        return 0;
    }

    echo "Test failed at line: $lineNumber\n\n";
    echo "Expression:          `$expression`\n\n";
    echo "Expected status is:  $expectedStatus\n";
    echo "Test     status is:  $exitStatus\n\n";
    echo "Expected result is:  $expectedResult\n";
    echo "Test     result is:  $result\n\n";

    if( $exitStatus === MathEvaluationFailure )
    {
        echo "Error:           $error\n";
    }

    echo "\n\n";

    return 1;
}