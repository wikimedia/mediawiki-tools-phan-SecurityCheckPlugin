<?php

function testDiff() {
	// In this test, the arrays may have different unsafe elements.
	$arr1 = [ 'a' => 'safe', 'b' => $_GET['unsafe'] ];
	$arr2 = [ 'b' => 'safe', 'c' => $_GET['another-unsafe-val'] ];
	$arr3 = [ 'c' => 'safe' ];
	echo array_diff( $arr1, $arr1 ); // TODO Safe
	echo array_diff( $arr1, $arr2 ); // Unsafe, because the unsafe elements can be different
	echo array_diff( $arr1, $arr3 ); // Unsafe
	echo array_diff( $arr2, $arr1 ); // Unsafe, because the unsafe elements can be different
	echo array_diff( $arr2, $arr2 ); // TODO Safe
	echo array_diff( $arr2, $arr3 ); // Unsafe
	echo array_diff( $arr3, $arr1 ); // TODO Safe
	echo array_diff( $arr3, $arr2 ); // TODO Safe
	echo array_diff( $arr3, $arr3 ); // Safe

	echo array_diff_assoc( $arr1, $arr1 ); // TODO Safe
	echo array_diff_assoc( $arr1, $arr2 ); // Unsafe
	echo array_diff_assoc( $arr1, $arr3 ); // Unsafe
	echo array_diff_assoc( $arr2, $arr1 ); // Unsafe
	echo array_diff_assoc( $arr2, $arr2 ); // TODO Safe
	echo array_diff_assoc( $arr2, $arr3 ); // Unsafe
	echo array_diff_assoc( $arr3, $arr1 ); // TODO Safe
	echo array_diff_assoc( $arr3, $arr2 ); // TODO Safe
	echo array_diff_assoc( $arr3, $arr3 ); // Safe

	echo array_diff_key( $arr1, $arr1 ); // TODO Safe
	echo array_diff_key( $arr1, $arr2 ); // TODO Safe
	echo array_diff_key( $arr1, $arr3 ); // Unsafe
	echo array_diff_key( $arr2, $arr1 ); // Unsafe
	echo array_diff_key( $arr2, $arr2 ); // TODO Safe
	echo array_diff_key( $arr2, $arr3 ); // TODO Safe
	echo array_diff_key( $arr3, $arr1 ); // TODO Safe
	echo array_diff_key( $arr3, $arr2 ); // TODO Safe
	echo array_diff_key( $arr3, $arr3 ); // Safe
}

function testIntersect() {
	// In this test, the arrays may have different unsafe elements.
	$arr1 = [ 'a' => 'safe', 'b' => $_GET['unsafe'] ];
	$arr2 = [ 'b' => 'safe', 'c' => $_GET['another-unsafe-val'] ];
	$arr3 = [ 'c' => 'safe' ];
	echo array_intersect( $arr1, $arr1 ); // Unsafe
	echo array_intersect( $arr1, $arr2 ); // Unsafe, because the unsafe elements can be identical
	echo array_intersect( $arr1, $arr3 ); // TODO Safe
	echo array_intersect( $arr2, $arr1 ); // Unsafe
	echo array_intersect( $arr2, $arr2 ); // Unsafe
	echo array_intersect( $arr2, $arr3 ); // TODO Safe
	echo array_intersect( $arr3, $arr1 ); // TODO Safe
	echo array_intersect( $arr3, $arr2 ); // TODO Safe
	echo array_intersect( $arr3, $arr3 ); // Safe

	echo array_intersect_assoc( $arr1, $arr1 ); // Unsafe
	echo array_intersect_assoc( $arr1, $arr2 ); // TODO Safe, 'b' can only be there if its value is 'safe'
	echo array_intersect_assoc( $arr1, $arr3 ); // TODO Safe
	echo array_intersect_assoc( $arr2, $arr1 ); // TODO Safe, 'b' can only be there if its value is 'safe'
	echo array_intersect_assoc( $arr2, $arr2 ); // Unsafe
	echo array_intersect_assoc( $arr2, $arr3 ); // TODO Safe
	echo array_intersect_assoc( $arr3, $arr1 ); // TODO Safe
	echo array_intersect_assoc( $arr3, $arr2 ); // TODO Safe
	echo array_intersect_assoc( $arr3, $arr3 ); // Safe

	echo array_intersect_key( $arr1, $arr1 ); // Unsafe
	echo array_intersect_key( $arr1, $arr2 ); // Unsafe, value for 'b' is taken from $arr1
	echo array_intersect_key( $arr1, $arr3 ); // TODO Safe
	echo array_intersect_key( $arr2, $arr1 ); // TODO Safe, value for 'b' is taken from $arr2
	echo array_intersect_key( $arr2, $arr2 ); // Unsafe
	echo array_intersect_key( $arr2, $arr3 ); // Unsafe, value for 'c' is taken from $arr2
	echo array_intersect_key( $arr3, $arr1 ); // TODO Safe
	echo array_intersect_key( $arr3, $arr2 ); // TODO Safe, value for 'c' is taken from $arr3
	echo array_intersect_key( $arr3, $arr3 ); // Safe
}

/**
 * Test for when the arrays have the same unsafe values. We don't look at values though, so this test is more of
 * an acknowledgement of existing limitations.
 */
function diffAndIntersectSameUnsafe() {
	$unsafe = $_GET['unsafe'];
	$arr1 = [ 'a' => 'safe', 'b' => $unsafe ];
	$arr2 = [ 'b' => 'safe', 'c' => $unsafe ];
	echo array_diff( $arr1, $arr2 ); // TODO Ideally safe because the unsafe elements are identical
	echo array_diff( $arr2, $arr1 ); // TODO Ideally safe because the unsafe elements are identical
	echo array_diff_assoc( $arr1, $arr2 ); // Unsafe
	echo array_diff_assoc( $arr2, $arr1 ); // Unsafe
	echo array_diff_key( $arr1, $arr2 ); // TODO Safe
	echo array_diff_key( $arr2, $arr1 ); // Unsafe
	echo array_intersect( $arr1, $arr2 ); // Unsafe
	echo array_intersect( $arr2, $arr1 ); // Unsafe
	echo array_intersect_assoc( $arr1, $arr2 ); // TODO Safe
	echo array_intersect_assoc( $arr2, $arr1 ); // TODO Safe
	echo array_intersect_key( $arr1, $arr2 ); // Unsafe, element for 'b' comes from $arr1
	echo array_intersect_key( $arr2, $arr1 ); // TODO Safe, element for 'b' comes from $arr2
}

function testDiffAndIntersectUnknownKeys() {
	$arr1 = [ 'a' => 'safe', $_GET['k'] => 'also-safe' ];
	$arr2 = [ 'b' => 'safe', $_GET['j'] => $_GET['unsafe'] ];
	echo array_diff( $arr1, $arr2 ); // Unsafe
	echo array_diff( $arr2, $arr1 ); // Unsafe
	echo array_diff_assoc( $arr1, $arr2 ); // Unsafe
	echo array_diff_assoc( $arr2, $arr1 ); // Unsafe
	echo array_diff_key( $arr1, $arr2 ); // Unsafe
	echo array_diff_key( $arr2, $arr1 ); // Unsafe
	echo array_intersect( $arr1, $arr2 ); // Unsafe
	echo array_values( array_intersect( $arr1, $arr2 ) ); // TODO Safe
	echo array_intersect( $arr2, $arr1 ); // Unsafe
	echo array_values( array_intersect( $arr2, $arr1 ) ); // Unsafe
	echo array_intersect_assoc( $arr1, $arr2 ); // Unsafe
	echo array_intersect_assoc( $arr2, $arr1 ); // Unsafe
	echo array_intersect_key( $arr1, $arr2 ); // Unsafe
	echo array_values( array_intersect_key( $arr1, $arr2 ) ); // TODO Safe
	echo array_intersect_key( $arr2, $arr1 ); // Unsafe
	echo array_values( array_intersect_key( $arr2, $arr1 ) ); // Unsafe
}
