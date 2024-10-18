<?php

// Test recursion where two or more functions are calling each other. When this happens, we want to make sure that
// 1 - we don't enter a potentially infinite analysis loop, and
// 2 - we don't just mark all the functions as unconditionally preserving all arguments

function firstFunction( $arg ) {
	if ( rand() ) {
		return secondFunction( $arg );
	}
	if ( rand() ) {
		return thirdFunction( $arg );
	}
	return 'safe';
}

function secondFunction( $arg ) {
	if ( rand() ) {
		return firstFunction( $arg );
	}
	if ( rand() ) {
		return thirdFunction( $arg );
	}
	return 'safe';
}

function thirdFunction( $arg ) {
	if ( rand() ) {
		return firstFunction( $arg );
	}
	if ( rand() ) {
		return secondFunction( $arg );
	}
	return 'safe';
}

echo firstFunction( $_GET['a'] ); // Safe
echo secondFunction( $_GET['b'] ); // Safe
echo thirdFunction( $_GET['b'] ); // Safe