<?php

// For binary addition (which is special-cased), see test 'addition'

function arithmeticRemovesTaint() {
	$unsafe = $_GET['x'];

	echo ( $unsafe - $GLOBALS['unknown'] ); // Safe
	echo ( $unsafe / $GLOBALS['unknown'] ); // Safe
	echo ( $unsafe % $GLOBALS['unknown'] ); // Safe
	echo ( $unsafe * $GLOBALS['unknown'] ); // Safe
	echo ( $unsafe ** $GLOBALS['unknown'] ); // Safe
}

/** This test is specifically detailed because appending is a common way to introduce taint */
function appendPreservesTaint() {
	$unsafe = $_GET['x'];
	$safe = 'safe';

	echo ( $unsafe . $_GET['unsafe'] ); // Unsafe
	echo ( $unsafe . $safe ); // Unsafe
	echo ( $safe . $unsafe ); // Unsafe
	echo ( $safe . 'safe' ); // Safe
}

function bitwisePreservesTaint() {
	$unsafe = $_GET['x'];

	echo ( $unsafe | $GLOBALS['unknown'] ); // Unsafe
	echo ( $unsafe & $GLOBALS['unknown'] ); // Unsafe
	echo ( $unsafe ^ $GLOBALS['unknown'] ); // Unsafe
}

function bitwiseShiftRemovesTaint() {
	$unsafe = $_GET['x'];

	echo ( $unsafe << $unsafe ); // Safe
	echo ( $unsafe >> $unsafe ); // Safe
}

function nullCoalescePreservesTaint() {
	$unsafe = $_GET['x'];
	echo ( $unsafe ?? $_GET['stillunsafe'] ); // Unsafe
}

function spaceshipRemovesTaint() {
	$unsafe = $_GET['x'];
	echo ( $unsafe <=> $unsafe ); // Safe
}

function boolOpsRemoveTaint() {
	$unsafe = $_GET['x'];

	echo ( $unsafe || $unsafe ); // Safe
	echo ( $unsafe or $unsafe ); // Safe
	echo ( $unsafe && $unsafe ); // Safe
	echo ( $unsafe and $unsafe ); // Safe
	echo ( $unsafe xor $unsafe ); // Safe
}

function comparisonsRemoveTaint() {
	$unsafe = $_GET['x'];
	$unsafe2 = $_GET['x'];

	echo ( $unsafe === $unsafe2 ); // Safe
	echo ( $unsafe !== $unsafe2 ); // Safe
	echo ( $unsafe == $unsafe2 ); // Safe
	echo ( $unsafe != $unsafe2 ); // Safe
	echo ( $unsafe < $unsafe2 ); // Safe
	echo ( $unsafe <= $unsafe2 ); // Safe
	echo ( $unsafe > $unsafe2 ); // Safe
	echo ( $unsafe >= $unsafe2 ); // Safe
}
