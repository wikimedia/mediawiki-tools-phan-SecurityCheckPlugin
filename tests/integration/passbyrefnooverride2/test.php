<?php

function maybeTaint( &$arg ) {
	if ( rand() ) {
		$arg = $_GET['x'];
	}
}
function alwaysTaint( &$arg ) {
	if ( rand() ) {
		$arg = $_GET['x'];
	} else {
		$arg = $_GET['x'];
	}
}
function maybeEscape( &$arg ) {
	if ( rand() ) {
		$arg = htmlspecialchars( $arg );
	}
}
function alwaysEscape( &$arg ) {
	if ( rand() ) {
		$arg = htmlspecialchars( $arg );
	} else {
		$arg = htmlspecialchars( $arg );
	}
}
function noop( &$arg ) {
}

$safe1 = 'x';
maybeTaint( $safe1 );
echo $safe1; // Unsafe

$unsafe1 = $_GET['x']; // This MUST be in caused-by
maybeTaint( $unsafe1 );
echo $unsafe1; // Unsafe

$safe2 = 'x';
alwaysTaint( $safe2 );
echo $safe2; // Unsafe

$unsafe2 = $_GET['y'];
alwaysTaint( $unsafe2 );
echo $unsafe2; // Unsafe, must NOT have line 42 in the caused-by

$unsafe3 = $_GET['foo'];
maybeEscape( $unsafe3 );
echo $unsafe3; // Unsafe, not caused by line 47

$unsafe4 = $_GET['foo'];
alwaysEscape( $unsafe4 );
echo $unsafe4; // Safe

$unsafe5 = $_GET['foo'];
noop( $unsafe5 );
echo $unsafe5; // Unsafe
