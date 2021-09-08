<?php

use Wikimedia\Rdbms\Database;

function getTaintedArray(): array {
	return $_GET['a'];
}

// NOTE: Use literals, because unknown strings could be numeric (e.g. "42") and would be coerced to int upon index write
function getSafeUnknown() {
	return rand() ? 42 : 'foobar';
}


$db = new Database();

$maybeSingle = $_GET['x'];
$db->insert( 'x', $maybeSingle ); // Unsafe

$maybeSingleWrapped = [ $_GET['x'] ];
$db->insert( 'x', $maybeSingleWrapped ); // Unsafe

$definitelyMultiple = [ getTaintedArray() ];
$db->insert( 'x', $definitelyMultiple ); // Unsafe

$multipleSafeRows = [];
foreach ( $_GET['x'] as $val ) {
	$multipleSafeRows[] = [
		'safe1' => $val,
		'safe2' => $_GET['v']
	];
}
$db->insert( 'x', $multipleSafeRows ); // Safe

$multipleUnsafeRows = [];
foreach ( $_GET['x'] as $val ) {
	$multipleUnsafeRows[] = [ $val, $_GET['v'] ];
}
$db->insert( 'x', $multipleUnsafeRows ); // Safe because all the keys are safe


$multipleUnknownRows = [];
foreach ( $_GET['x'] as $val ) {
	$multipleUnknownRows[] = [
		getSafeUnknown() => $val
	];
}
$db->insert( 'x', $multipleUnknownRows ); // Safe


$multipleSafeRowsAddition = [];
$baseSafeRow = [ 'safe' => $_GET['x'] ];
foreach ( $_GET['v'] as $val ) {
	$multipleSafeRowsAddition[] = $baseSafeRow + [
		'safe1' => $val,
		'safe2' => $_GET['y']
	];
}
$db->insert( 'x', $multipleSafeRowsAddition ); // Safe

$multipleUnsafeRowsAddition = [];
foreach ( $_GET['v'] as $val ) {
	$multipleUnsafeRowsAddition[] = $baseSafeRow + [
		$val,
		$_GET['y']
	];
}
$db->insert( 'x', $multipleUnsafeRowsAddition ); // Safe because all keys are safe

$multipleUnknownRowsAddition = [];
foreach ( $_GET['v'] as $val ) {
	$multipleUnknownRowsAddition[] = $baseSafeRow + [
		getSafeUnknown() => $_GET['y']
	];
}
$db->insert( 'x', $multipleUnknownRowsAddition ); // Safe


function storeMultipleSafe( $data ) {
	$tuples = [];
	foreach ( $data as $val ) {
		$tuples[] = [ 'safe' => $val ];
	}

	$db = new Database;
	$db->insert( 'x', $tuples );
}

storeMultipleSafe( $_GET['a'] ); // Safe
storeMultipleSafe( getTaintedArray() ); // Safe


function storeMultipleUnsafe( $data ) {
	$tuples = [];
	foreach ( $data as $val ) {
		$tuples[] = [ $val => 'safevalue' ];
	}

	$db = new Database;
	$db->insert( 'x', $tuples );
}

storeMultipleUnsafe( $_GET['a'] ); // Unsafe
storeMultipleUnsafe( getTaintedArray() ); // Unsafe


function storeMultipleUnknown( $data ) {
	$tuples = [];
	foreach ( $data as $val ) {
		$tuples[] = [ getSafeUnknown() => $val ];
	}

	$db = new Database;
	$db->insert( 'x', $tuples );
}

storeMultipleUnknown( $_GET['a'] ); // Safe
storeMultipleUnknown( getTaintedArray() ); // Safe

function storeMultipleSafeExplicitKey( $data ) {
	$tuples = [];
	foreach ( $data as $val ) {
		$tuples[0] = [ 'safe' => $val ];
	}

	$db = new Database;
	$db->insert( 'x', $tuples );
}

storeMultipleSafeExplicitKey( $_GET['a'] ); // Safe
storeMultipleSafeExplicitKey( getTaintedArray() ); // Safe
