<?php
//
//  command line
//



require_once( "matheval.php" );



if( isset( $argv[1] ) )
{
    $result = matheval( $argv[1], $error );

    if( $result === false )
    {
        echo "Error: $error\n";
    }
    else
    {
        echo "$result\n";
    }
}