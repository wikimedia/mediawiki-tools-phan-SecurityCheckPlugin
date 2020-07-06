<?php

$array = [
	'outer' => [
		'safe' => $_GET['x'],
		'unsafe' => 'safe',
		'inner1' => [
			'good' => [
				'foo' => 'safe'
			],
			$GLOBALS['unknown'] => [
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
echo $fill1['a']['safe']; // TODO: Ideally Safe
echo $fill1['a']['unsafe']; // Unsafe
foreach ( $fill1 as $k1 => $_ ) {
	echo $k1; // TODO: Ideally Safe
}
$unsafeKeys = [ $GLOBALS['a'], $GLOBALS['b'] ];
$fill2 = array_fill_keys( $unsafeKeys, $value );
echo $fill2['a']; // Unsafe
echo $fill2['a']['safe']; // TODO: Ideally Safe
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

function diffAndIntersect() {
	$arr1 = [ 'a' => 'safe', 'b' => $_GET['unsafe'] ];
	$arr2 = [ 'b' => 'safe', 'c' => $_GET['unsafe'] ];
	$arr3 = [ 'c' => 'safe' ];
	echo array_diff( $arr1, $arr2 ); // TODO Ideally safe
	echo array_diff( $arr2, $arr1 ); // Unsafe
	echo array_diff( $arr2, $arr3 ); // TODO Ideally safe
	echo array_diff( $arr3, $arr2 ); // TODO Ideally safe
	echo array_intersect( $arr1, $arr2 ); // Unsafe
	echo array_intersect( $arr2, $arr3 ); // Unsafe
	echo array_intersect( $arr3, $arr2 ); // TODO Ideally safe
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
