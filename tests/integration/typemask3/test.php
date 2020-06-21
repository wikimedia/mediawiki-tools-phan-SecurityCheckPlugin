<?php

function safeEcho1() {
	$var = $_GET['foo'];
	if ( !is_int( $var ) ) {
		return;
	}
	echo $var; // Safe
}

function safeEcho2() {
	$var = $_GET['foo'];
	if ( !( $var instanceof SafeClass ) ) {
		return;
	}
	echo $var; // Safe
}

function safeEcho3() {
	$var = $_GET['foo'];
	if ( !is_int( $var ) ) {
		$var = 42;
	}
	echo $var; // Safe
}

function unsafeEcho1() {
	$var = $_GET['foo'];
	if ( !is_int( $var ) ) {
		$var = $_GET['baz'];
	}
	echo $var; // Unsafe
}

function unsafeEcho2() {
	$var = $_GET['foo'];
	if ( $var instanceof UnsafeClass ) {
		return;
	}
	echo $var; // Unsafe, can be another dangerous type
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
