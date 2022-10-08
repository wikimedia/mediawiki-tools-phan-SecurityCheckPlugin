<?php

use Wikimedia\Rdbms\Database;

// Ensure that backpropagation works well with NUMKEY taint. This uses Database::select just for
// convenience (and because it's the main reason why NUMKEY taint exists).

/**
 * @return-taint sql_numkey
 */
function getPureNumkey() {
	return 'placeholder';
}

/**
 * @param-taint $arg exec_sql_numkey,exec_html
 */
function execNumkeyAndHTML( $arg ) {
	return 'placeholder';
}

/**
 * Helper to get an unknown type but without taint
 * @return-taint none
 */
function unknownType() {
	return $GLOBALS['unknown'];
}


function unsafe1( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', $unsafe );
}
unsafe1( $_GET['x'] );

function unsafe2( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ $unsafe ] );
}
unsafe2( $_GET['x'] );

function unsafe3( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ unknownType() => $unsafe ] );
}
unsafe3( $_GET['x'] );

function unsafe4( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ 'safe' => $unsafe, $unsafe ] );
}
unsafe4( $_GET['x'] );

function unsafe5( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ 'safe' => $unsafe, [ $unsafe ] ] );
}
unsafe5( $_GET['x'] );

function unsafe6( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ (int)unknownType() => $unsafe ] );
}
unsafe6( $_GET['x'] );

function unsafe7( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', $unsafe );
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
	$db = new Database;
	$db->select( 'a', 'b', $unsafe + [ 'safe' => 'foo' ] );
}
unsafe11( $_GET['x'] );

function unsafe12( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', array_merge( $unsafe, [ 'safe' => 'foo' ] ) );
}
unsafe12( $_GET['x'] );// TODO: Unsafe, but since using the 'return' option in backpropagateArgTaint $unsafe is not picked up

function unsafe13( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', rand() ? $unsafe : [ 'safe' => 'foo' ] );
}
unsafe13( $_GET['x'] );

function unsafe14( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ $unsafe ] + [ 'safe' => 'foo' ] );
}
unsafe14( $_GET['x'] );

function unsafe15( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', array_merge( [ $unsafe ], [ 'safe' => 'foo' ] ) );
}
unsafe15( $_GET['x'] );// TODO: Unsafe, but since using the 'return' option in backpropagateArgTaint $unsafe is not picked up

function unsafe16( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', rand() ? [ $unsafe ] : [ 'safe' => 'foo' ] );
}
unsafe16( $_GET['x'] );

function unsafe17( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', $unsafe );
}
unsafe17( getPureNumkey() );

function unsafe18( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ [ 'unsafe' => $unsafe ] ] );
}
unsafe18( $_GET['x'] ); // TODO: This is unsafe, because NUMKEY should only affect the outer array

function unsafe19( $unsafe ) {
	$db = new Database;
	$stuff = [
		$unsafe
	];
	$db->select( 'a', 'b', $stuff );
}
unsafe19( $_GET['x'] );

function unsafe20( $unsafe ) {
	$db = new Database;
	$stuff = [
		unknownType() => $unsafe
	];
	$db->select( 'a', 'b', $stuff );
}
unsafe20( $_GET['x'] );

function unsafe21( $unsafe ) {
	$db = new Database;
	$stuff = [
		$unsafe,
		'safe' => $unsafe
	];
	$db->select( 'a', 'b', $stuff );
}
unsafe21( $_GET['x'] );

function unsafe22( $unsafe ) {
	$db = new Database;
	$k = (int)rand();
	$stuff = [
		$k => $unsafe
	];
	$db->select( 'a', 'b', $stuff );
}
unsafe22( $_GET['x'] );

function unsafe23( $unsafe ) {
	$db = new Database;
	$stuff = [
		(string)unknownType() => $unsafe // PHP will autocast numeric strings to integer, hence unsafe: https://github.com/phan/phan/issues/4344
	];
	$db->select( 'a', 'b', $stuff );
}
unsafe23( $_GET['x'] );

function unsafe24( $unsafe ) {
	$db = new Database;
	$k = (string)rand(); // PHP will autocast numeric strings to integer, hence unsafe: https://github.com/phan/phan/issues/4344
	$stuff = [
		$k => $unsafe
	];
	$db->select( 'a', 'b', $stuff );
}
unsafe24( $_GET['x'] );

function safe1( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ 'safe' => $unsafe ] );
}
safe1( $_GET['x'] );

function safe2( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ (string)unknownType() => $unsafe ] );
}
safe2( $_GET['x'] );

function safe3( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ 'safe' => $unsafe, 'safe2' => $unsafe ] );
}
safe3( $_GET['x'] );

function safe4( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', $unsafe );
}
safe4( [ 'safe' => $_GET['x'] ] );

function safe5( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ 'safe' => $unsafe, 'safe2' => [ $unsafe ] ] );
}
safe5( $_GET['x'] );

function safe6( $unsafe ) {
	execNumkeyAndHTML( [ 'safe' => $unsafe ] );
}
safe6( $_GET['x'] ); // Not totally safe, there's still an XSS, but no SQLi.

function safe7( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ 'safe1' => $unsafe ] + [ 'safe' => 'foo' ] );
}
safe7( $_GET['x'] );

function safe8( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', array_merge( [ 'safe1' => $unsafe ], [ 'safe' => 'foo' ] ) );
}
safe8( $_GET['x'] );

function safe9( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', rand() ? [ 'safe' => $unsafe ] : [ 'safe' => 'foo' ] );
}
safe9( $_GET['x'] );

function safe10( $unsafe ) {
	$db = new Database;
	$db->select( 'a', 'b', [ 'safe' => $unsafe ] );
}
safe10( getPureNumkey() );

function safe11( $unsafe ) {
	$db = new Database;
	$stuff = [
		'safe' => $unsafe
	];
	$db->select( 'a', 'b', $stuff );
}
safe11( $_GET['x'] );
