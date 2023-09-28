<?php

$safeKeysSafeVals = [ 'foo', 'bar' => 'baz' ];
$safeKeysUnsafeVals = [ $_GET['a'], 'bar' => $_GET['b'] ];
$unsafeKeysSafeVals = [ $_GET['a'] => 'foo' ];
$unsafeKeysUnsafeVals = [ $_GET['a'] => $_GET['b'] ];

// array_unique

echo array_unique( $safeKeysSafeVals ); // Safe
foreach ( array_unique( $safeKeysSafeVals ) as $k => $v ) {
	echo $k; // Safe
	echo $v; // Safe
}
echo array_unique( $safeKeysUnsafeVals ); // Unsafe
foreach ( array_unique( $safeKeysUnsafeVals ) as $k => $v ) {
	echo $k; // Safe
	echo $v; // Unsafe
}
echo array_unique( $unsafeKeysSafeVals ); // Unsafe
foreach ( array_unique( $unsafeKeysSafeVals ) as $k => $v ) {
	echo $k; // Unsafe
	echo $v; // Safe
}
echo array_unique( $unsafeKeysUnsafeVals ); // Unsafe
foreach ( array_unique( $unsafeKeysUnsafeVals ) as $k => $v ) {
	echo $k; // Unsafe
	echo $v; // Unsafe
}

// array_filter

echo array_filter( $safeKeysSafeVals ); // Safe
foreach ( array_filter( $safeKeysSafeVals ) as $k => $v ) {
	echo $k; // Safe
	echo $v; // Safe
}
echo array_filter( $safeKeysUnsafeVals ); // Unsafe
foreach ( array_filter( $safeKeysUnsafeVals ) as $k => $v ) {
	echo $k; // Safe
	echo $v; // Unsafe
}
echo array_filter( $unsafeKeysSafeVals ); // Unsafe
foreach ( array_filter( $unsafeKeysSafeVals ) as $k => $v ) {
	echo $k; // Unsafe
	echo $v; // Safe
}
echo array_filter( $unsafeKeysUnsafeVals ); // Unsafe
foreach ( array_filter( $unsafeKeysUnsafeVals ) as $k => $v ) {
	echo $k; // Unsafe
	echo $v; // Unsafe
}

// array_chunk

echo array_chunk( $safeKeysSafeVals, 2 ); // Safe
foreach ( array_chunk( $safeKeysSafeVals, 2 ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // Safe
	echo array_values( $arr ); // Safe
}
echo array_chunk( $safeKeysSafeVals, 2, false ); // Safe
foreach ( array_chunk( $safeKeysSafeVals, 2, false ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // Safe
	echo array_values( $arr ); // Safe
}
echo array_chunk( $safeKeysSafeVals, 2, true ); // Safe
foreach ( array_chunk( $safeKeysSafeVals, 2 ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // Safe
	echo array_values( $arr ); // Safe
}

echo array_chunk( $safeKeysUnsafeVals, 2 ); // Unsafe
foreach ( array_chunk( $safeKeysUnsafeVals, 2 ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // Safe
	echo array_values( $arr ); // Unsafe
}
echo array_chunk( $safeKeysUnsafeVals, 2, false ); // Unsafe
foreach ( array_chunk( $safeKeysUnsafeVals, 2, false ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // Safe
	echo array_values( $arr ); // Unsafe
}
echo array_chunk( $safeKeysUnsafeVals, 2, true ); // Unsafe
foreach ( array_chunk( $safeKeysUnsafeVals, 2 ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // Safe
	echo array_values( $arr ); // Unsafe
}

echo array_chunk( $unsafeKeysSafeVals, 2 ); // TODO Safe
foreach ( array_chunk( $unsafeKeysSafeVals, 2 ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // TODO Safe
	echo array_values( $arr ); // Safe
}
echo array_chunk( $unsafeKeysSafeVals, 2, false ); // TODO Safe
foreach ( array_chunk( $unsafeKeysSafeVals, 2, false ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // TODO Safe
	echo array_values( $arr ); // Safe
}
echo array_chunk( $unsafeKeysSafeVals, 2, true ); // Unsafe
foreach ( array_chunk( $unsafeKeysSafeVals, 2 ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // Unsafe
	echo array_values( $arr ); // Safe
}

echo array_chunk( $unsafeKeysUnsafeVals, 2 ); // Unsafe
foreach ( array_chunk( $unsafeKeysUnsafeVals, 2 ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // TODO Safe
	echo array_values( $arr ); // Unsafe
}
echo array_chunk( $unsafeKeysUnsafeVals, 2, false ); // Unsafe
foreach ( array_chunk( $unsafeKeysUnsafeVals, 2, false ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // TODO Safe
	echo array_values( $arr ); // Unsafe
}
echo array_chunk( $unsafeKeysUnsafeVals, 2, true ); // Unsafe
foreach ( array_chunk( $unsafeKeysUnsafeVals, 2 ) as $outerKey => $arr ) {
	echo $outerKey; // Safe
	echo array_keys( $arr ); // Unsafe
	echo array_values( $arr ); // Unsafe
}