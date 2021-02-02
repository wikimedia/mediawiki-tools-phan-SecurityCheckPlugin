<?php

$getUnsafe = fn() => $_GET['unsafe'];
echo $getUnsafe(); // Unsafe
$getSafe = fn() => 'safe';
echo $getSafe(); // Safe
$appendUnsafe = fn( $x ) => $x . $_GET['a'];
echo $appendUnsafe( 'safe' );// Unsafe
$appendSafe = fn( $x ) => $x . 'safe';
echo $appendUnsafe( $_GET['unsafe'] );// Unsafe

$escape = fn( $x ) => htmlspecialchars( $x );
echo $escape( $_GET['a'] ); // Safe

function testDoesntAlterOuterScope() {
	$unsafe = $_GET['unsafe'];
	$fn = fn( $x ) => $x = 42;
	$fn( $unsafe );
	echo $unsafe; // Still unsafe.
}

$fn = fn() => $_GET['unsafe'];
echo $fn; // Doesn't make sense, but it's not unsafe, nor unknown.
