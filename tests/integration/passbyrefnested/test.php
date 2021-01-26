<?php

// Regression test for a function passing through a ref argument

function recursiveCall( &$par ) {
	if ( rand() ) {
		$par = $_GET['x'];
	}
	if ( rand() ) {
		recursiveCall( $par ); // It doesn't matter whether this line appears in the caused-by lines
	}
}

$var1 = '';
recursiveCall( $var1 );
echo $var1;

function recursiveCallInfinite( &$par ) {
	$par = $_GET['x'];
	recursiveCallInfinite( $par ); // It doesn't matter whether this line appears in the caused-by lines
}

$var2 = '';
recursiveCallInfinite( $var2 );
echo $var2;


function myFunc1( &$par1 ) {
	$par1 = $_GET['x'];
}
function myFunc2( &$par2 ) {
	myFunc1( $par2 );
}

$var3 = '';
myFunc2( $var3 );
echo $var3;


function longChain1( &$par1 ) { $par1 = $_GET['x']; }
function longChain2( &$par2 ) { longChain1( $par2 ); }
function longChain3( &$par2 ) { longChain2( $par2 ); }
function longChain4( &$par2 ) { longChain3( $par2 ); }
function longChain5( &$par2 ) { longChain4( $par2 ); }

$var4 = '';
longChain5( $var4 );
// NOTE: The following *IS* unsafe, but phan limits the recursion depth, and will only analyze the
// last two functions. Increasing the 'maximum_recursion_depth' option will work just fine, but it
// comes at the cost of slowness and memory consumption.
echo $var4;
