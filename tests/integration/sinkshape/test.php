<?php

class TestSinkShape {
	static function sinkKeys( $x ) {
		// Placeholder: this method is hardcoded. Only the keys of $x are EXECed.
	}

	static function sinkAll( $x ) {
		// Placeholder: this method is hardcoded. All of $x are EXECed.
	}
}

$allUnsafe = $_GET['a'];
TestSinkShape::sinkKeys( $allUnsafe ); // Unsafe
TestSinkShape::sinkAll( $allUnsafe ); // Unsafe

$safeKeysSafeValues = [
	'foo',
	'bar' => 'baz'
];
TestSinkShape::sinkKeys( $safeKeysSafeValues ); // Safe
TestSinkShape::sinkAll( $safeKeysSafeValues ); // Safe

$safeKeysUnsafeValues = [
	$_GET['a'],
	'bar' => $_GET['b']
];
TestSinkShape::sinkKeys( $safeKeysUnsafeValues ); // Safe
TestSinkShape::sinkAll( $safeKeysUnsafeValues ); // Unsafe

$unsafeKeysUnsafeValues = [
	$_GET['a'] => $_GET['b']
];
TestSinkShape::sinkKeys( $unsafeKeysUnsafeValues ); // Unsafe
TestSinkShape::sinkAll( $unsafeKeysUnsafeValues ); // Unsafe

$unsafeKeysSafeValues = [
	$_GET['a'] => 'foo'
];
TestSinkShape::sinkKeys( $unsafeKeysSafeValues ); // Unsafe
TestSinkShape::sinkAll( $unsafeKeysSafeValues ); // Unsafe

// Test array plus for keys
TestSinkShape::sinkKeys( $allUnsafe + $safeKeysSafeValues ); // Unsafe
TestSinkShape::sinkKeys( $safeKeysSafeValues + $safeKeysUnsafeValues ); // Safe
TestSinkShape::sinkKeys( $safeKeysUnsafeValues + $safeKeysUnsafeValues ); // Safe
TestSinkShape::sinkKeys( $safeKeysUnsafeValues + $unsafeKeysUnsafeValues ); // Unsafe
TestSinkShape::sinkKeys( $unsafeKeysUnsafeValues + $unsafeKeysSafeValues ); // Unsafe


class SinkKeysIndirect {
	private $propForKeys = [];
	private $propForValues = [];

	function setKey( $val ) {
		$this->propForKeys[$val] = 'foo';
	}

	function setVal( $val ) {
		$this->propForValues['foo'] = $val;
	}

	function recordDependencies() {
		$this->setKey( $_GET['a'] );
		$this->setVal( $_GET['a'] );
	}

	function trigger() {
		TestSinkShape::sinkKeys( $this->propForKeys ); // TODO: Unsafe
		TestSinkShape::sinkAll( $this->propForKeys ); // TODO: Unsafe

		TestSinkShape::sinkKeys( $this->propForValues ); // Safe
		TestSinkShape::sinkAll( $this->propForValues ); // Unsafe
	}
}