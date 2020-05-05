<?php

/* Tests for constant conditionals. These are not fully implemented yet. */

function neverTainted( $arg ) {
	if ( false ) {
		$arg = $_GET['x'];
	}
	echo $arg; // Safe
}

function alwaysTainted( $arg ) {
	if ( true ) {
		$arg = $_GET['x'];
	}
	echo $arg; // Unsafe
}

function neverEscaped( $arg ) {
	if ( rand() ) {
		$arg = $_GET['x'];
	}
	if ( false ) {
		$arg = htmlspecialchars( $arg );
	}
	echo $arg; // Unsafe
}

function alwaysEscaped( $arg ) {
	if ( rand() ) {
		$arg = $_GET['x'];
	}
	if ( true ) {
		$arg = htmlspecialchars( $arg );
	}
	echo $arg; // Safe
}

function neverTainted2( $arg ) {
	if ( true ) {
		if ( rand() ) {
			if ( false ) {
				$arg .= $_GET['x'];
			}
		} else {
			$arg = 'safe';
		}
	}
	if ( rand() ) {
		$arg = htmlspecialchars( $arg );
	}
	echo $arg; // Safe
}

function neverTaintedRef( &$ref ) {
	if ( false ) {
		$ref = $_GET['x'];
	}
}
function alwaysTaintedRef( &$ref ) {
	if ( true ) {
		$ref = $_GET['x'];
	}
}
function neverEscapedRef( &$ref ) {
	if ( false ) {
		$ref = htmlspecialchars( $ref );
	}
}
function alwaysEscapedRef( &$ref ) {
	if ( true ) {
		$ref = htmlspecialchars( $ref );
	}
}

$ref = 'x';
neverTaintedRef( $ref );
echo $ref; // Safe
$ref = 'x';
alwaysTaintedRef( $ref );
echo $ref; // Unsafe
$ref = $_GET['foo'];
neverEscapedRef( $ref );
echo $ref; // Unsafe
$ref = $_GET['foo'];
alwaysEscapedRef( $ref );
echo $ref; // Safe
