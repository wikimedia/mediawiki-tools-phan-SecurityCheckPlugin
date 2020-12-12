<?php
function getStuff() {
	return [ $_GET['a'], $_GET['b'] ];
}

list( $foo ) = getStuff();

echo $foo;

function getSafeStuff() {
	return [ 'a', 'b' ];
}

list( $a, $b ) = getSafeStuff();
echo $b;

function testIncomplete1() {
	$arr = [ 'safe', $_GET['unsafe'], 'safe' ];
	[ , $unsafe ] = $arr;
	echo $unsafe; // Unsafe
}

function testIncomplete2() {
	$arr = [ $_GET['unsafe'], 'safe' ];
	[ , $safe ] = $arr;
	echo $safe; // Safe
}
