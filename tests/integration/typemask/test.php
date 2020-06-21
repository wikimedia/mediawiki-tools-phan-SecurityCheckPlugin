<?php

function testTypeMask() {
	$var = $_GET['foo'];
	if ( is_int( $var ) ) {
		echo $var; // Safe
	}
	if ( $var === 'foo' ) {
		echo $var; // Safe
	}
	if ( is_string( $var ) ) {
		echo $var; // Unsafe
	}
	if ( $var instanceof SafeClass ) {
		echo $var; // Safe
	}
	if ( $var instanceof UnsafeClass ) {
		echo $var; // Unsafe
	}
}

class SafeClass {
	public function __toString() {
		return 'barbaz';
	}
}

class UnsafeClass {
	public function __toString() {
		return (string)$_GET['baz'];
	}
}

function partlyUnsafe1( $par ) {
	if ( is_int( $par ) ) {
		echo $par;
	}
	echo $par;
}
partlyUnsafe1( $_GET['foo'] );

function partlyUnsafe2( $par ) {
	if ( is_int( $par ) ) {
		`$par`;
	}
	`$par`;
}
partlyUnsafe2( $_GET['foo'] );

function partlyUnsafe3( $par ) {
	if ( is_int( $par ) ) {
		require $par;
	}
	require $par;
}
partlyUnsafe3( $_GET['foo'] );
