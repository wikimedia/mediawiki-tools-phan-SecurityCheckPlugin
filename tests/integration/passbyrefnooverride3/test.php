<?php

function identicalSides( &$arg ) {
	$arg = $arg; // TODO Should be in caused-by
}

$var1 = $_GET['baz']; // Note: this MUST be in caused-by
identicalSides( $var1 );
echo $var1;


function indirect( &$arg ) {
	$temp = $arg;// TODO This and the next line should be in caused-by
	$arg = $temp;
}

$var2 = $_GET['baz']; // Note: this MUST be in caused-by
indirect( $var2 );
echo $var2;


function usingList( &$arg ) {
	[ $foo, $_ ] = $arg;// TODO This and the next line should be in caused-by
	$arg = $foo;
}

$var3 = $_GET['baz']; // Note: this MUST be in caused-by
usingList( $var3 );
echo $var3;


function ternary( &$arg ) {
	[ $foo, $_ ] = $arg ?: [ 'baz' ];// TODO This and the next line should be in caused-by
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
