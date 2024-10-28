<?php

function identicalSides( &$arg ) {
	$arg = $arg;
}

$var1 = $_GET['baz'];
identicalSides( $var1 );
echo $var1; // Unsafe, TODO caused by lines 7, 8 and 4 (in this order)


function indirect( &$arg ) {
	$temp = $arg;// This and the next line should be in caused-by
	$arg = $temp;
}

$var2 = $_GET['baz'];
indirect( $var2 );
echo $var2;// Unsafe, TODO caused by lines 17, 18, 14, 13 (in this order)


function usingList( &$arg ) {
	[ $foo, $_ ] = $arg;// This and the next line should be in caused-by
	$arg = $foo;
}

$var3 = $_GET['baz'];
usingList( $var3 );
echo $var3;// Unsafe, TODO caused by lines 27, 28, 24, 23 (in this order)


function ternary( &$arg ) {
	[ $foo, $_ ] = $arg ?: [ 'baz' ];// This and the next line should be in caused-by
	$arg = $foo;
}

$var4 = $_GET['baz'];
ternary( $var4 );
echo $var4;// Unsafe, TODO caused by lines 37, 38, 34, 33 (in this order)


function indirectLink( &$arg ) {
	$temp = $arg;
	echo $temp;
}

$var2 = $_GET['baz']; // Note: this MUST be in caused-by
indirectLink( $var2 );// Unsafe, caused by lines 43, 44, 47
