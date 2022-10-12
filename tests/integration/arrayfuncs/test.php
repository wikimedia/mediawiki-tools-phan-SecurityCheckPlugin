<?php

$array = [
	'outer' => [
		'safe' => $_GET['x'],
		'unsafe' => 'safe',
		'inner1' => [
			'good' => [
				'foo' => 'safe'
			],
			$_GET['unsafe unknown'] => [
				'foo' => $_GET['unsafe']
			]
		],
		'inner2' => [
			'first' => [
				'safe' => 'safe'
			],
			'second' => [
				'safe' => 'safe'
			]
		],
		'inner3' => [
			$_GET['foo'] => 'safe',
		]
	],
	'safe' => 'safe',
	'unsafe' => $_GET['a']
];

echo array_pop( $array ); // Unsafe
echo array_pop( $array )['unsafe']; // Unsafe (can be $array['outer']['safe']['unsafe']
echo current( $array['outer'] ); // Unsafe
echo reset( $array['outer']['inner2'] )['safe']; // Safe
echo next( $array['outer']['inner1'] )['foo']; // Unsafe

echo array_change_key_case( $array['outer'] )['safe']; // Unsafe
echo array_change_key_case( $array['outer'] )['unsafe']; // Safe

echo array_keys( $array ); // Safe
echo array_keys( $array['outer']['inner1'] ); // Unsafe

echo array_values( $array['outer']['inner1'] ); // Unsafe
echo array_values( $array ); // Unsafe
echo array_values( $array['outer']['inner3'] ); // Safe

$value = [ 'safe' => 'safe', 'unsafe' => $_GET['a'] ];
$safeKeys = [ 'a', 'b', 'c' ];
$fill1 = array_fill_keys( $safeKeys, $value );
echo $fill1['a']; // Unsafe
echo $fill1['a']['safe']; // Safe
echo $fill1['a']['unsafe']; // Unsafe
foreach ( $fill1 as $k1 => $_ ) {
	echo $k1; // Safe
}
$unsafeKeys = [ $_GET['a'], $_GET['b'] ];
$fill2 = array_fill_keys( $unsafeKeys, $value );
echo $fill2['a']; // Unsafe
echo $fill2['a']['safe']; // Safe
echo $fill2['a']['unsafe']; // Unsafe
foreach ( $fill2 as $k2 => $_ ) {
	echo $k2; // Unsafe
}

function valueFromUnknownKey() {
	$arr = [ 'safe' => 'safe', $GLOBALS['x'] => $_GET['a'] ];
	$val = end( $arr );
	echo $val; // Unsafe
}

function flipUnknown() {
	$arr = [ $_GET['unknown'] => 'foo' ];
	echo array_flip( $arr );
}

function arrayImplode() {
	$safeKeysSafeValues = [ 'a' => 'safe', 'b' => 'safe' ];
	echo implode( ',', $safeKeysSafeValues ); // Safe
	$safeKeysUnsafeValues = [ 'a' => 'safe', 'b' => $_POST['x'] ];
	echo implode( ',', $safeKeysUnsafeValues ); // Unsafe
	$unsafeKeysSafeValues = [ $_GET['a'] => 'safe', $_GET['b'] => 'safe' ];
	echo implode( ',', $unsafeKeysSafeValues ); // Safe
	$unsafeKeysUnsafeValues = [ $_GET['a'] => 'safe', $_GET['b'] => $_POST['x'] ];
	echo implode( ',', $unsafeKeysUnsafeValues ); // Unsafe
}

function arrayFill() {
	$safe = array_fill( $GLOBALS['start'], $_GET['count'], 'safe' );
	echo $safe; // Safe
	$unsafe = array_fill( 0, 5, $_GET['x'] );
	echo $unsafe; // Unsafe
}


function arrayCombine() {
	$safeArr = [ 'apple', 'pear', 'peach' ];
	$unsafeArr = [ $_GET['a'], $_GET['b'] ];

	$safeKeysUnsafeValues = array_combine( $safeArr, $unsafeArr );
	echo $safeKeysUnsafeValues; // Unsafe
	foreach ( $safeKeysUnsafeValues as $k => $v ) {
		echo $k; // Safe
		echo $v; // Unsafe
	}
	$unsafeKeysSafeValues = array_combine( $unsafeArr, $safeArr );
	echo $unsafeKeysSafeValues; // Unsafe
	foreach ( $unsafeKeysSafeValues as $k => $v ) {
		echo $k; // Unsafe
		echo $v; // Safe
	}

	$unsafeKeysSafeValues2 = array_combine( $safeKeysUnsafeValues, $unsafeKeysSafeValues );
	echo $unsafeKeysSafeValues2; // Unsafe
	foreach ( $unsafeKeysSafeValues2 as $k => $v ) {
		echo $k; // Unsafe
		echo $v; // Safe
	}
	$safeKeysUnsafeValues2 = array_combine( $unsafeKeysSafeValues, $safeKeysUnsafeValues );
	echo $safeKeysUnsafeValues2; // Unsafe
	foreach ( $safeKeysUnsafeValues2 as $k => $v ) {
		echo $k; // Safe
		echo $v; // Unsafe
	}
}

function testArrayChangeKeyCase() {
	$arr = [ 'x' => 'safe', 'X' => $_GET['a'], 'y' => $_GET['b'], 'z' => $_GET['c'], 'Z' => 'safe' ];
	$lowerImplicit = array_change_key_case( $arr );
	$lowerExplicit = array_change_key_case( $arr, CASE_LOWER );
	$upper = array_change_key_case( $arr, CASE_UPPER );

	echo $lowerImplicit['x']; // TODO Unsafe, element exists
	echo $lowerImplicit['X']; // TODO Safe, element does not exist
	echo $lowerImplicit['y']; // Unsafe, element exists
	echo $lowerImplicit['Y']; // Safe, element does not exist
	echo $lowerImplicit['z']; // TODO Safe, element exists
	echo $lowerImplicit['Z']; // Safe, element does not exist

	echo $lowerExplicit['x']; // TODO Unsafe, element exists
	echo $lowerExplicit['X']; // TODO Safe, element does not exist
	echo $lowerExplicit['y']; // Unsafe, element exists
	echo $lowerExplicit['Y']; // Safe, element does not exist
	echo $lowerExplicit['z']; // TODO Safe, element exists
	echo $lowerExplicit['Z']; // Safe, element does not exist

	echo $upper['x']; // Safe, element does not exist
	echo $upper['X']; // Unsafe, element exists
	echo $upper['y']; // TODO Safe, element does not exist
	echo $upper['Y']; // TODO Unsafe, element exists
	echo $upper['z']; // TODO Safe, element does not exist
	echo $upper['Z']; // Safe, element exists
}

/**
 * Test to ensure there are no crashes with literal arguments
 * @suppress SecurityCheck-XSS
 */
function testLiteralArguments() {
	echo array_values( [ 1, 2, 3 ] );
	echo array_keys( [ 1, 2, 3 ] );
	echo array_diff( [], $_GET );
	echo array_merge( [], $_GET, [] );
}
