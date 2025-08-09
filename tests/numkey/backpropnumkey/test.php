<?php

// Ensure that backpropagation works well with NUMKEY taint.

function unsafe1( $unsafe ) {
	execNumkey( $unsafe );
}
unsafe1( $_GET['x'] );

function unsafe2( $unsafe ) {
	execNumkey( [ $unsafe ] );
}
unsafe2( $_GET['x'] );

function unsafe3( $unsafe ) {
	execNumkey( [ unknownType() => $unsafe ] );
}
unsafe3( $_GET['x'] );

function unsafe4( $unsafe ) {
	execNumkey( [ 'safe' => $unsafe, $unsafe ] );
}
unsafe4( $_GET['x'] );

function unsafe5( $unsafe ) {
	execNumkey( [ 'safe' => $unsafe, [ $unsafe ] ] );
}
unsafe5( $_GET['x'] );

function unsafe6( $unsafe ) {
	execNumkey( [ (int)unknownType() => $unsafe ] );
}
unsafe6( $_GET['x'] );

function unsafe7( $unsafe ) {
	execNumkey( $unsafe );
}
unsafe7( [ $_GET['x'] ] );

function unsafe8( $unsafe ) {
	execNumkeyAndHTML( $unsafe );
}
unsafe8( $_GET['x'] );

function unsafe9( $unsafe ) {
	execNumkeyAndHTML( [ $unsafe ] );
}
unsafe9( $_GET['x'] );

function unsafe10( $unsafe ) {
	execNumkeyAndHTML( $unsafe );
}
unsafe10( htmlspecialchars( $_GET['x'] ) );

function unsafe11( $unsafe ) {
	execNumkey( $unsafe + [ 'safe' => 'foo' ] );
}
unsafe11( $_GET['x'] );

function unsafe12( $unsafe ) {
	execNumkey( array_merge( $unsafe, [ 'safe' => 'foo' ] ) );
}
unsafe12( $_GET['x'] );// TODO: Unsafe, but since using the 'return' option in backpropagateArgTaint $unsafe is not picked up

function unsafe13( $unsafe ) {
	execNumkey( rand() ? $unsafe : [ 'safe' => 'foo' ] );
}
unsafe13( $_GET['x'] );

function unsafe14( $unsafe ) {
	execNumkey( [ $unsafe ] + [ 'safe' => 'foo' ] );
}
unsafe14( $_GET['x'] );

function unsafe15( $unsafe ) {
	execNumkey( array_merge( [ $unsafe ], [ 'safe' => 'foo' ] ) );
}
unsafe15( $_GET['x'] );// TODO: Unsafe, but since using the 'return' option in backpropagateArgTaint $unsafe is not picked up

function unsafe16( $unsafe ) {
	execNumkey( rand() ? [ $unsafe ] : [ 'safe' => 'foo' ] );
}
unsafe16( $_GET['x'] );

function unsafe17( $unsafe ) {
	execNumkey( $unsafe );
}
unsafe17( getPureNumkey() );

function unsafe18( $unsafe ) {
	execNumkey( [ [ 'unsafe' => $unsafe ] ] );
}
unsafe18( $_GET['x'] ); // TODO: This is unsafe, because NUMKEY should only affect the outer array

function unsafe19( $unsafe ) {
	$stuff = [
		$unsafe
	];
	execNumkey( $stuff );
}
unsafe19( $_GET['x'] );

function unsafe20( $unsafe ) {
	$stuff = [
		unknownType() => $unsafe
	];
	execNumkey( $stuff );
}
unsafe20( $_GET['x'] );

function unsafe21( $unsafe ) {
	$stuff = [
		$unsafe,
		'safe' => $unsafe
	];
	execNumkey( $stuff );
}
unsafe21( $_GET['x'] );

function unsafe22( $unsafe ) {
	$k = (int)rand();
	$stuff = [
		$k => $unsafe
	];
	execNumkey( $stuff );
}
unsafe22( $_GET['x'] );

function unsafe23( $unsafe ) {
	$stuff = [
		(string)unknownType() => $unsafe // PHP will autocast numeric strings to integer, hence unsafe: https://github.com/phan/phan/issues/4344
	];
	execNumkey( $stuff );
}
unsafe23( $_GET['x'] );

function unsafe24( $unsafe ) {
	$k = (string)rand(); // PHP will autocast numeric strings to integer, hence unsafe: https://github.com/phan/phan/issues/4344
	$stuff = [
		$k => $unsafe
	];
	execNumkey( $stuff );
}
unsafe24( $_GET['x'] );

function safe1( $unsafe ) {
	execNumkey( [ 'safe' => $unsafe ] );
}
safe1( $_GET['x'] );

function safe2( $unsafe ) {
	execNumkey( [ (string)unknownType() => $unsafe ] );
}
safe2( $_GET['x'] );

function safe3( $unsafe ) {
	execNumkey( [ 'safe' => $unsafe, 'safe2' => $unsafe ] );
}
safe3( $_GET['x'] );

function safe4( $unsafe ) {
	execNumkey( $unsafe );
}
safe4( [ 'safe' => $_GET['x'] ] );

function safe5( $unsafe ) {
	execNumkey( [ 'safe' => $unsafe, 'safe2' => [ $unsafe ] ] );
}
safe5( $_GET['x'] );

function safe6( $unsafe ) {
	execNumkeyAndHTML( [ 'safe' => $unsafe ] );
}
safe6( $_GET['x'] ); // Not totally safe, there's still an XSS, but no SQLi.

function safe7( $unsafe ) {
	execNumkey( [ 'safe1' => $unsafe ] + [ 'safe' => 'foo' ] );
}
safe7( $_GET['x'] );

function safe8( $unsafe ) {
	execNumkey( array_merge( [ 'safe1' => $unsafe ], [ 'safe' => 'foo' ] ) );
}
safe8( $_GET['x'] );

function safe9( $unsafe ) {
	execNumkey( rand() ? [ 'safe' => $unsafe ] : [ 'safe' => 'foo' ] );
}
safe9( $_GET['x'] );

function safe10( $unsafe ) {
	execNumkey( [ 'safe' => $unsafe ] );
}
safe10( getPureNumkey() );

function safe11( $unsafe ) {
	$stuff = [
		'safe' => $unsafe
	];
	execNumkey( $stuff );
}
safe11( $_GET['x'] );
