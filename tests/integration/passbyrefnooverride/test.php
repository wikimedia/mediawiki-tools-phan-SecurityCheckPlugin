<?php

// For consecutive pass-by-ref calls, ensure that we correctly keep track of the order to determine
// whether the overall taint should be overridden.

function inFunctionScope1() {
	$var1 = '';
	safe( $var1 );
	unsafe( $var1 );
	echo $var1;
}

/* In global scope 1 */
$var2 = '';
safe( $var2 );
unsafe( $var2 );
echo $var2;

function inFunctionScope2() {
	$var3 = '';
	unsafe( $var3 );
	safe( $var3 );
	echo $var3;
}

/* In global scope */
$var4 = '';
unsafe( $var4 );
safe( $var4 );
echo $var4;

function unsafe( &$extraArg1 ) {
$extraArg1 = $_GET['unsafe'];
}
function safe( &$extraArg2 ) {
$extraArg2 = 'Foo';
}


$var5 = $_GET['unsafe'];
unsafe( $var5 );
echo $var5; // Unsafe, must NOT have line 40 in the caused-by

function maybeReplaceTaint( &$arg ) {
	if ( rand() ) {
		if ( rand() ) {
			$arg = $_GET['x'];
		} else {
			$arg = $_GET['z'];
		}
	}
}

$var6 = $_GET['unsafe']; // This MUST be in caused-by
maybeReplaceTaint( $var6 );
echo $var6;
