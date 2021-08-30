<?php

/** @return-taint none */
function getPossiblyEmptySafeArray(): array {
	return $_GET['foo'];
}

function testOverrideInBodyUnsafe() {
	$val = $_GET['a'];
	foreach ( getPossiblyEmptySafeArray() as $x ) {
		$val = 'safe';
	}
	echo $val; // Unsafe
}

function testOverrideInValueUnsafe() {
	$val = $_GET['a'];
	foreach ( getPossiblyEmptySafeArray() as $val ) {
	}
	echo $val; // Unsafe
}

function testOverrideInKeyUnsafe() {
	$val = $_GET['a'];
	foreach ( getPossiblyEmptySafeArray() as $val => $_ ) {
	}
	echo $val; // Unsafe
}

function testOverrideInBodySafe() {
	$array = getPossiblyEmptySafeArray();
	assert( count( $array ) > 0 );
	$val = $_GET['a'];
	foreach ( $array as $x ) {
		$val = 'safe';
	}
	echo $val; // TODO Safe
}

function testOverrideInValueSafe() {
	$array = getPossiblyEmptySafeArray();
	assert( count( $array ) > 0 );
	$val = $_GET['a'];
	foreach ( $array as $val ) {
	}
	echo $val; // TODO Safe
}

function testOverrideInKeySafe() {
	$array = getPossiblyEmptySafeArray();
	assert( count( $array ) > 0 );
	$val = $_GET['a'];
	foreach ( $array as $val => $_ ) {
	}
	echo $val; // TODO Safe
}

class TestWithProps {
	private $prop1, $prop2, $prop3, $prop4, $prop5, $prop6;

	public function __construct() {
		$this->prop1 = $_GET['a'];
		$this->prop2 = $_GET['a'];
		$this->prop3 = $_GET['a'];
		$this->prop4 = $_GET['a'];
		$this->prop5 = $_GET['a'];
		$this->prop6 = $_GET['a'];
	}

	function testOverrideInBodyUnsafe() {
		foreach ( getPossiblyEmptySafeArray() as $x ) {
			$this->prop1 = 'safe';
		}
		echo $this->prop1; // Unsafe
	}

	function testOverrideInValueUnsafe() {
		foreach ( getPossiblyEmptySafeArray() as $this->prop2 ) {
		}
		echo $this->prop2; // Unsafe
	}

	function testOverrideInKeyUnsafe() {
		foreach ( getPossiblyEmptySafeArray() as $this->prop3 => $_ ) {
		}
		echo $this->prop3; // Unsafe
	}

	function testOverrideInBodySafe() {
		$array = getPossiblyEmptySafeArray();
		assert( count( $array ) > 0 );
		foreach ( $array as $x ) {
			$this->prop4 = 'safe';
		}
		echo $this->prop4; // TODO: Ideally safe, but we never override taint on props
	}

	function testOverrideInValueSafe() {
		$array = getPossiblyEmptySafeArray();
		assert( count( $array ) > 0 );
		foreach ( $array as $this->prop5 ) {
		}
		echo $this->prop5; // TODO: Ideally safe, but we never override taint on props
	}

	function testOverrideInKeySafe() {
		$array = getPossiblyEmptySafeArray();
		assert( count( $array ) > 0 );
		foreach ( $array as $this->prop6 => $_ ) {
		}
		echo $this->prop6; // TODO: Ideally safe, but we never override taint on props
	}
}

$glob1 = $_GET['a'];
function testGlobalOverrideInBodyUnsafe() {
	global $glob1;
	foreach ( getPossiblyEmptySafeArray() as $x ) {
		$glob1 = 'safe';
	}
	echo $glob1; // Unsafe
}

$glob2 = $_GET['a'];
function testGlobalOverrideInValueUnsafe() {
	global $glob2;
	foreach ( getPossiblyEmptySafeArray() as $glob2 ) {
	}
	echo $glob2; // Unsafe
}

$glob3 = $_GET['a'];
function testGlobalOverrideInKeyUnsafe() {
	global $glob3;
	foreach ( getPossiblyEmptySafeArray() as $glob3 => $_ ) {
	}
	echo $glob3; // Unsafe
}

$glob4 = $_GET['a'];
function testGlobalOverrideInBodySafe() {
	global $glob4;
	$array = getPossiblyEmptySafeArray();
	assert( count( $array ) > 0 );
	foreach ( $array as $x ) {
		$glob4 = 'safe';
	}
	echo $glob4; // TODO: Ideally safe, but we never override taint on globals
}

$glob5 = $_GET['a'];
function testGlobalOverrideInValueSafe() {
	global $glob5;
	$array = getPossiblyEmptySafeArray();
	assert( count( $array ) > 0 );
	foreach ( $array as $glob5 ) {
	}
	echo $glob5; // TODO: Ideally safe, but we never override taint on globals
}

$glob6 = $_GET['a'];
function testGlobalOverrideInKeySafe() {
	global $glob6;
	$array = getPossiblyEmptySafeArray();
	assert( count( $array ) > 0 );
	foreach ( $array as $glob6 => $_ ) {
	}
	echo $glob6; // TODO: Ideally safe, but we never override taint on globals
}
