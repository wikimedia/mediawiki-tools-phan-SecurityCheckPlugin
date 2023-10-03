<?php

class TestSinkShape {
	static function sinkKeys( $x ) {
		// Placeholder: this method is hardcoded. Only the keys of $x are EXECed.
	}

	static function sinkAll( $x ) {
		// Placeholder: this method is hardcoded. All of $x are EXECed.
	}

	static function sinkKeysOfUnknown( $x ) {
		// Placeholder: this method is hardcoded. The keys of unknown elements in $x are EXECed.
	}
}

$allUnsafe = $_GET['a'];
TestSinkShape::sinkKeys( $allUnsafe ); // Unsafe
TestSinkShape::sinkAll( $allUnsafe ); // Unsafe
TestSinkShape::sinkKeysOfUnknown( $allUnsafe ); // Unsafe

$safeKeysSafeValues = [
	'foo',
	'bar' => 'baz'
];
TestSinkShape::sinkKeys( $safeKeysSafeValues ); // Safe
TestSinkShape::sinkAll( $safeKeysSafeValues ); // Safe
TestSinkShape::sinkKeysOfUnknown( $safeKeysSafeValues ); // Safe

$safeKeysUnsafeValues = [
	$_GET['a'],
	'bar' => $_GET['b']
];
TestSinkShape::sinkKeys( $safeKeysUnsafeValues ); // Safe
TestSinkShape::sinkAll( $safeKeysUnsafeValues ); // Unsafe
TestSinkShape::sinkKeysOfUnknown( $safeKeysUnsafeValues ); // Unsafe

$unsafeKeysUnsafeValues = [
	$_GET['a'] => $_GET['b']
];
TestSinkShape::sinkKeys( $unsafeKeysUnsafeValues ); // Unsafe
TestSinkShape::sinkAll( $unsafeKeysUnsafeValues ); // Unsafe
TestSinkShape::sinkKeysOfUnknown( $unsafeKeysUnsafeValues ); // Unsafe

$unsafeKeysSafeValues = [
	$_GET['a'] => 'foo'
];
TestSinkShape::sinkKeys( $unsafeKeysSafeValues ); // Unsafe
TestSinkShape::sinkAll( $unsafeKeysSafeValues ); // Unsafe
TestSinkShape::sinkKeysOfUnknown( $unsafeKeysSafeValues ); // Safe

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
		TestSinkShape::sinkKeys( $this->propForKeys ); // Unsafe
		TestSinkShape::sinkAll( $this->propForKeys ); // Unsafe
		TestSinkShape::sinkKeysOfUnknown( $this->propForKeys ); // Safe

		TestSinkShape::sinkKeys( $this->propForValues ); // Safe
		TestSinkShape::sinkAll( $this->propForValues ); // Unsafe
		TestSinkShape::sinkKeysOfUnknown( $this->propForValues ); // Unsafe
	}
}

function sinkKeyOfUnknownOnArrayOfArgAsKnownEl( $arg ) {
	$array = [];
	$array[42] = [ 'some-key' => $arg ];
	TestSinkShape::sinkKeysOfUnknown( $array );
}
sinkKeyOfUnknownOnArrayOfArgAsKnownEl( $_GET['a'] ); // Safe

function sinkKeyOfUnknownOnArrayOfArgAsUnknownEl( $arg ) {
	$array = [];
	foreach ( $arg as $val ) {
		$array[] = [ 'some-key' => $val ];
	}
	TestSinkShape::sinkKeysOfUnknown( $array );
}
sinkKeyOfUnknownOnArrayOfArgAsUnknownEl( $_GET['a'] ); // Safe