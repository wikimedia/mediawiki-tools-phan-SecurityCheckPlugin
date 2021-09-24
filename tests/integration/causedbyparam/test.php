<?php

function concatArgs( $arg1, $arg2 ) {
	$x = $arg1;
	$x .= $arg2;
	return $x;
}
echo concatArgs( 's', $_GET['b'] ); // Only lines 5 and 6 in the caused-by
echo concatArgs( $_GET['b'], 's' ); // Only lines 4 and 6 in the caused-by
echo concatArgs( $_GET['b'], $_GET['x'] ); // Lines 4, 5 and 6 in the caused-by


function concatArgAndEvil( $arg ) {
	$x = $arg;
	$x .= $_GET['evil'];
	return $x;
}
echo concatArgAndEvil( 's' ); // Only lines 15 and 16 in the caused-by
echo concatArgAndEvil( $_GET['b'] ); // Lines 14, 15 and 16 in the caused-by

function concatEscapedArgAndEvil( $arg ) {
	$x = htmlspecialchars( $arg );
	$x .= $_GET['evil'];
	return $x;
}
echo concatEscapedArgAndEvil( 's' ); // Only lines 23 and 24 in the caused-by
echo concatEscapedArgAndEvil( $_GET['b'] ); // Only lines 23 and 24 in the caused-by
require concatEscapedArgAndEvil( 's' ); // Only lines 23 and 24 in the caused-by
require concatEscapedArgAndEvil( $_GET['b'] ); // TODO: Lines 22, 23 and 24 in the caused-by


function concatArgInterleaved( $arg ) {
	$x = $arg;
	$x .= $_GET['evil'];
	$x .= $arg;
	return $x;
}
echo concatArgInterleaved( 's' ); // Only lines 34 and 36 in the caused-by
echo concatArgInterleaved( $_GET['b'] ); // Lines 33-36 in the caused-by.

function concatArgInterleaved2( string $a, string $b ) {
	$x = $_GET['a'];
	$x .= $a;
	$x .= $_GET['b'];
	$x .= $b;
	return $x;
}
echo concatArgInterleaved2( 'a', 'b' ); // Lines 42, 44, 46
echo concatArgInterleaved2( $_GET['x'], 'b' ); // Lines 42, 43, 44, 46
echo concatArgInterleaved2( 'a', $_GET['y'] ); // Lines 42, 44, 45, 46
echo concatArgInterleaved2( $_GET['x'], $_GET['y'] ); // Lines 42-46

function concatArgAndEvilSameLine( string $x ) {
	$r = $x . $_GET['a'];
	return $r;
}
echo concatArgAndEvilSameLine( 's' ); // Lines 54, 55
echo concatArgAndEvilSameLine( $_GET['y'] ); // Lines 54, 55


function returnEscapedParam( $par ) {
	$x = $par;
	return htmlspecialchars( $x );
}
htmlspecialchars( returnEscapedParam( [] ) );// Line 63 only, in particular NOT line 62. NOTE: For some reason, the argument to returnEscapedParam must be [] here.


function escapeAndReturnParam( $par ) {
	$encValue = htmlspecialchars( $par );
	return $par . $encValue;
}

escapeAndReturnParam( htmlspecialchars( 'foo' ) );// Line 69 only, in particular NOT line 70.


function escapeAndReturnParamWithIntermediateAssignment( $par ) {
	$encValue = htmlspecialchars( $par );
	$ret = $par . $encValue;
	return $ret;
}

escapeAndReturnParamWithIntermediateAssignment( htmlspecialchars( 'foo' ) );// Line 77 only, in particular NOT lines 78 and 79.
