<?php

function getUnsafe1( $arg ) {
	$x = $arg['unsafe'];
	return $x;
}
echo getUnsafe1( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Unsafe

function getUnsafe2( $arg ) {
	return $arg['unsafe'];
}
echo getUnsafe2( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Unsafe

function getSafe1( $arg ) {
	$x = $arg['safe'];
	return $x;
}
echo getSafe1( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Safe

function getSafe2( $arg ) {
	return $arg['safe'];
}
echo getSafe2( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Safe

function getSafeSafe( $arg ) {
	$x = $arg['safe']['safe'];
	return $x;
}
echo getSafeSafe( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] );// Safe

function getSafeSafe2( $arg ) {
	$x = $arg['safe'];
	return $x['safe'];
}
echo getSafeSafe2( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] );// Safe

function getSafeSafe3( $arg ) {
	return $arg['safe']['safe'];
}
echo getSafeSafe3( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] );// Safe

function getSafeUnsafe( $arg ) {
	$x = $arg['safe']['unsafe'];
	return $x;
}
echo getSafeUnsafe( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function getSafeUnsafe2( $arg ) {
	$x = $arg['safe'];
	return $x['unsafe'];
}
echo getSafeUnsafe2( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function getSafeUnsafe3( $arg ) {
	return $arg['safe']['unsafe'];
}
echo getSafeUnsafe3( [ 'unsafe' => ['safe' => 'xx', 'unsafe' => $_GET['a'] ], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function getUnsafeSafe( $arg ) {
	return $arg['unsafe']['safe'];
}
echo getUnsafeSafe( [ 'unsafe' => ['safe' => 'xx', 'unsafe' => $_GET['a'] ], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Safe

function getSafeOrUnsafeRandom( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];
	} else {
		$x = $arg['unsafe'];
	}
	return $x;
}
echo getSafeOrUnsafeRandom( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] ); // Unsafe

function getSafeOrUnsafeRandom2( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];
	} else {
		$y = $arg['safe'];
		$x = $y['unsafe'];
	}
	return $x;
}
echo getSafeOrUnsafeRandom2( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function getRandomAlwaysSafe( $arg ) {
	if ( rand() ) {
		$z = $arg;
		$y = $z['safe'];
		$x = $y['safe2'];
	} else {
		$y = $arg['safe'];
		$x = $y['safe'];
	}
	return $x;
}
echo getRandomAlwaysSafe( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'], 'safe2' => 'safe' ] ] ); // Safe

function getRandomDifferentDims( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];//Note, this is an array with a tainted element
	} else {
		$y = $arg['safe'];
		$x = $y['safe'];
	}
	return $x;
}
echo getRandomDifferentDims( [ 'unsafe' => $_GET['a'], 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Unsafe

function getUnknown( $arg ) {
	$x = $arg[$GLOBALS['x']];
	return $x;
}
echo getUnknown( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Unsafe

function getMaybeUnknown( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];
	} else {
		$x = $arg[$GLOBALS['x']];
	}
	return $x;
}
echo getMaybeUnknown( [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ] );//Unsafe


function echoArgArray( $arg ) {
	echo [ 'x' => $arg, 'y' => 'safe' ];
}
echoArgArray( $_GET['a'] ); // Unsafe
echoArgArray( [ 'x' => $_GET['a'], 'y' => 'safe' ] ); // Unsafe
echoArgArray( [ 'y' => $_GET['a'], 'x' => 'safe' ] ); // Unsafe
