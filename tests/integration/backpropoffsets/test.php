<?php

function echoUnsafe( $arg ) {
	$x = $arg['unsafe'];
	echo $x;
}
echoUnsafe( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Unsafe

function echoUnsafe2( $arg ) {
	echo $arg['unsafe'];
}
echoUnsafe2( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Unsafe

function echoSafe( $arg ) {
	$x = $arg['safe'];
	echo $x;
}
echoSafe( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Safe

function echoSafe2( $arg ) {
	echo $arg['safe'];
}
echoSafe2( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Safe

function echoSafeSafe( $arg ) {
	$x = $arg['safe']['safe'];
	echo $x;
}
echoSafeSafe( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] );// Safe

function echoSafeUnsafe( $arg ) {
	$x = $arg['safe']['unsafe'];
	echo $x;
}
echoSafeUnsafe( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function echoSafeSafe2( $arg ) {
	$x = $arg['safe'];
	$y = $x['safe'];
	echo $y;
}
echoSafeSafe2( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] );// Safe

function echoSafeUnsafe2( $arg ) {
	$x = $arg['safe'];
	$y = $x['unsafe'];
	echo $y;
}
echoSafeUnsafe2( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function echoSafeSafe3( $arg ) {
	echo $arg['safe']['safe'];
}
echoSafeSafe3( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] );// Safe

function echoSafeUnsafe3( $arg ) {
	echo $arg['safe']['unsafe'];
}
echoSafeUnsafe3( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function echoSafeOrUnsafeRandom( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];
	} else {
		$x = $arg['unsafe'];
	}
	echo $x;
}
echoSafeOrUnsafeRandom( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] ); // Unsafe

function echoSafeOrUnsafeRandom2( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];
	} else {
		$y = $arg['safe'];
		$x = $y['unsafe'];
	}
	echo $x;
}
echoSafeOrUnsafeRandom2( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function echoRandomAlwaysSafe( $arg ) {
	if ( rand() ) {
		$z = $arg;
		$y = $z['safe'];
		$x = $y['safe2'];
	} else {
		$y = $arg['safe'];
		$x = $y['safe'];
	}
	echo $x;
}
echoRandomAlwaysSafe( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'], 'safe2' => 'safe' ] ] ); // Safe

function echoRandomDifferentDims( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];//Note, this is an array with a tainted element
	} else {
		$y = $arg['safe'];
		$x = $y['safe'];
	}
	echo $x;
}
echoRandomDifferentDims( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function echoUnknown( $arg ) {
	$x = $arg[$GLOBALS['x']];
	echo $x;
}
echoUnknown( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Unsafe

function echoMaybeUnknown( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];
	} else {
		$x = $arg[$GLOBALS['x']];
	}
	echo $x;
}
echoMaybeUnknown( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Unsafe

function testNoCrash( $arg ) {// Test deduplication of offset combinations with mixed types
	if ( rand() ) {
		$x = $arg[0];// This must be integer
	} else {
		$x = $arg[$GLOBALS['x']];//This must be unknown
	}
	echo $x;
}
testNoCrash( [] );


function withoff( $arg ) {
	$arg['x'] = $arg['x'];// Ensure the offset at the LHS is merged correctly
	echo $arg['x'];
}
withoff( [ 'x' => [ 'x' => 'safe', 'y' => $_GET['unsafe'] ] ] );// Unsafe

function withoff2( $arg ) {
	$arg['x'] = $arg['x'];// Ensure the offset at the LHS is merged correctly
	echo $arg;
}
withoff( [ 'x' => [ 'x' => 'safe', 'y' => $_GET['unsafe'] ] ] );// Unsafe

function withoff3( $arg ) {
	$arg[$GLOBALS['unknown safe']] = $arg[$GLOBALS['unknown safe 2']];// Ensure the offset at the LHS is merged correctly
	echo $arg;
}
withoff( [ 'x' => [ 'x' => 'safe', 'y' => $_GET['unsafe'] ] ] );// Unsafe
