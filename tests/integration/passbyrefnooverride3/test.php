<?php

function identicalSides( &$arg ) {
	$arg = $arg;
}

$var1 = $_GET['baz']; // Note: this MUST be in caused-by
identicalSides( $var1 );
echo $var1;


function indirect( &$arg ) {
	$temp = $arg;
	$arg = $temp;
}

$var2 = $_GET['baz']; // Note: this MUST be in caused-by
indirect( $var2 );
echo $var2;


function usingList( &$arg ) {
	[ $foo, $_ ] = $arg;
	$arg = $foo;
}

$var3 = $_GET['baz']; // Note: this MUST be in caused-by
usingList( $var3 );
echo $var3;


function ternary( &$arg ) {
	[ $foo, $_ ] = $arg ?: [ 'baz' ];
	$arg = $foo;
}

$var4 = $_GET['baz']; // Note: this MUST be in caused-by
ternary( $var4 );
echo $var4;


function indirectLink( &$arg ) {
	$temp = $arg;
	echo $temp;
}

$var2 = $_GET['baz']; // Note: this MUST be in caused-by
indirectLink( $var2 );
