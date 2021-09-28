<?php

function identicalSides( &$arg ) {
	$arg = $arg;
}

$var1 = $_GET['baz'];
identicalSides( $var1 );
echo $var1; // Unsafe, TODO caused by lines 8, 7 and 4


function indirect( &$arg ) {
	$temp = $arg;// This and the next line should be in caused-by
	$arg = $temp;
}

$var2 = $_GET['baz'];
indirect( $var2 );
echo $var2;// Unsafe, TODO caused by lines 18, 17, 14, 13


function usingList( &$arg ) {
	[ $foo, $_ ] = $arg;// This and the next line should be in caused-by
	$arg = $foo;
}

$var3 = $_GET['baz'];
usingList( $var3 );
echo $var3;// Unsafe, TODO caused by lines 28, 27, 24, 23


function ternary( &$arg ) {
	[ $foo, $_ ] = $arg ?: [ 'baz' ];// This and the next line should be in caused-by
	$arg = $foo;
}

$var4 = $_GET['baz'];
ternary( $var4 );
echo $var4;// Unsafe, TODO caused by lines 38, 37, 34, 33


function indirectLink( &$arg ) {
	$temp = $arg;
	echo $temp;
}

$var2 = $_GET['baz']; // Note: this MUST be in caused-by
indirectLink( $var2 );// Unsafe, caused by lines 47, 44, 43
