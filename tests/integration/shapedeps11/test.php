<?php

class TestKeyOfWrappedArg {
	public static function knownWrapper( $arg ) {
		$array = [ 'wrapped' => $arg['unsafe'] ];
		echo $array;
	}

	public static function unknownWrapper( $arg ) {
		$array = [ rand() => $arg['unsafe'] ];
		echo $array;
	}

	public static function asWrapperKey( $arg ) {
		$array = [ $arg['unsafe'] => 'literal' ];
		echo $array;
	}
}

'@taint-check-debug-method-first-arg TestKeyOfWrappedArg::knownWrapper';
TestKeyOfWrappedArg::knownWrapper( $_GET['a'] ); // XSS
TestKeyOfWrappedArg::knownWrapper( [ 'unsafe' => $_GET['a'] ] ); // XSS
TestKeyOfWrappedArg::knownWrapper( [ 'other' => $_GET['a'] ] ); // Safe
TestKeyOfWrappedArg::knownWrapper( [ 'other' => $_GET['a'], 'unsafe' => 'literal' ] ); // Safe

'@taint-check-debug-method-first-arg TestKeyOfWrappedArg::unknownWrapper';
TestKeyOfWrappedArg::unknownWrapper( $_GET['a'] ); // XSS
TestKeyOfWrappedArg::unknownWrapper( [ 'unsafe' => $_GET['a'] ] ); // XSS
TestKeyOfWrappedArg::unknownWrapper( [ 'other' => $_GET['a'] ] ); // Safe
TestKeyOfWrappedArg::unknownWrapper( [ 'other' => $_GET['a'], 'unsafe' => 'literal' ] ); // Safe

'@taint-check-debug-method-first-arg TestKeyOfWrappedArg::asWrapperKey';
TestKeyOfWrappedArg::asWrapperKey( $_GET['a'] ); // XSS
TestKeyOfWrappedArg::asWrapperKey( [ 'unsafe' => $_GET['a'] ] ); // XSS
TestKeyOfWrappedArg::asWrapperKey( [ 'other' => $_GET['a'] ] ); // Safe
TestKeyOfWrappedArg::asWrapperKey( [ 'other' => $_GET['a'], 'unsafe' => 'literal' ] ); // Safe

