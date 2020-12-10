<?php

function nonArrayAdditionRemovesTaint() {
	$unsafe = $_GET['x'];
	$unsafe += 42;
	echo $unsafe; // Safe

	$num = 42;
	$num += $_GET['unsafe'];
	echo $num; // Safe

	$u1 = $_GET['u1'];
	$u2 = $_GET['u2'];
	assert( !is_array( $u1 ) && !is_array( $u2 ) );
	$u1 += $u2;
	echo $u1; // TODO: Ideally safe because `array` is ruled out above, but phan doesn't infer a real type here.

	$u3 = $GLOBALS['u3'];
	$u4 = $GLOBALS['u4'];
	assert( !is_array( $u3 ) && !is_array( $u4 ) );
	$u3 += $u4;
	echo $u3; // Unsafe due to mixed type

	$u5 = $_GET['u5'];
	$u6 = $_GET['u6'];
	$u5 += $u6;
	echo $u5; // Unsafe
}

function unknownAdditionPreservesTaint() {
	$unsafe = $_GET['x'];
	$unsafe += $GLOBALS['unknown'];
	echo $unsafe; // Unsafe, could be an array

	$unsafe = $_GET['x'];
	$unsafe += [ 'safe' ];
	echo $unsafe; // Unsafe, could be an array
}

function arithmeticRemovesTaint() {
	$unsafe = $_GET['x'];
	$unsafe -= $GLOBALS['unknown'];
	echo $unsafe; // Safe

	$unsafe = $_GET['x'];
	$unsafe /= $GLOBALS['unknown'];
	echo $unsafe; // Safe

	$unsafe = $_GET['x'];
	$unsafe %= $GLOBALS['unknown'];
	echo $unsafe; // Safe

	$unsafe = $_GET['x'];
	$unsafe *= $GLOBALS['unknown'];
	echo $unsafe; // Safe

	$unsafe = $_GET['x'];
	$unsafe **= $GLOBALS['unknown'];
	echo $unsafe; // Safe
}

/** This test is specifically detailed because appending is a common way to introduce taint */
function appendPreservesTaint() {
	$unsafe = $_GET['x'];
	$unsafe .= $_GET['unsafe'];
	echo $unsafe; // Unsafe

	$unsafe = $_GET['x'];
	$unsafe .= 'safe';
	echo $unsafe; // Unsafe

	$safe = 'safe';
	$safe .= $_GET['unsafe'];
	echo $safe; // Unsafe

	$safe = 'safe';
	$safe .= 'safe';
	echo $safe; // Safe
}

function bitwisePreservesTaint() {
	$unsafe = $_GET['x'];
	$unsafe |= $GLOBALS['unknown'];
	echo $unsafe; // Unsafe

	$unsafe = $_GET['x'];
	$unsafe &= $GLOBALS['unknown'];
	echo $unsafe; // Unsafe

	$unsafe = $_GET['x'];
	$unsafe ^= $GLOBALS['unknown'];
	echo $unsafe; // Unsafe
}

function bitwiseShiftRemovesTaint() {
	$unsafe = $_GET['x'];
	$unsafe <<= $unsafe;
	echo $unsafe; // Safe

	$unsafe = $_GET['x'];
	$unsafe >>= $unsafe;
	echo $unsafe; // Safe
}

function nullCoalescePreservesTaint() {
	$unsafe = $_GET['x'];
	$unsafe ??= $_GET['y'];
	echo $unsafe; // Unsafe
}
