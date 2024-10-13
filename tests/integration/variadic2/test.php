<?php

class VariadicFunctions {
	public static function execArgArray( ...$args ) {
		echo $args;
	}

	public static function execEachArg( ...$args ) {
		foreach ( $args as $arg ) {
			echo $arg;
		}
	}

	public static function execDim42OfEachArg( ...$args ) {
		foreach ( $args as $arg ) {
			echo $arg[42];
		}
	}

	public static function execDimOfEachArg( ...$args ) {
		foreach ( $args as $arg ) {
			foreach ( $arg as $value ) {
				echo $value;
			}
		}
	}

	public static function execKeyOfArgArray( ...$args ) {
		foreach ( $args as $key => $arg ) {
			echo $key;
		}
	}

	public static function execKeyOfEachArg( ...$args ) {
		foreach ( $args as $arg ) {
			foreach ( $arg as $key => $value ) {
				echo $key;
			}
		}
	}
}

'@taint-check-debug-method-first-arg VariadicFunctions::execArgArray';
VariadicFunctions::execArgArray( 'safe', $_GET['unsafe'] ); // Unsafe

'@taint-check-debug-method-first-arg VariadicFunctions::execEachArg';
VariadicFunctions::execEachArg( 'safe', $_GET['unsafe'] ); // Unsafe

'@taint-check-debug-method-first-arg VariadicFunctions::execDim42OfEachArg';
VariadicFunctions::execDim42OfEachArg( 'safe', $_GET['unsafe'] ); // Unsafe
VariadicFunctions::execDim42OfEachArg( [ 42 => $_GET['unsafe'] ] ); // Unsafe
VariadicFunctions::execDim42OfEachArg( [ 'thisisnotexeced' => $_GET['unsafe'] ] ); // Safe
VariadicFunctions::execDim42OfEachArg( [ $_GET['keysarenotexeced'] => 'safe' ] ); // Safe

'@taint-check-debug-method-first-arg VariadicFunctions::execDimOfEachArg';
VariadicFunctions::execDimOfEachArg( 'safe', $_GET['unsafe'] ); // Unsafe
VariadicFunctions::execDimOfEachArg( [ 'anykeycangohere' => $_GET['unsafe'] ] ); // Unsafe
VariadicFunctions::execDimOfEachArg( [ $_GET['keysarenotexeced'] => 'safe' ] ); // Safe

'@taint-check-debug-method-first-arg VariadicFunctions::execKeyOfArgArray';
VariadicFunctions::execKeyOfArgArray( 'safe', $_GET['unsafe'] ); // Safe
VariadicFunctions::execKeyOfArgArray( [ 'somekey' => $_GET['unsafe'] ] ); // Safe
VariadicFunctions::execKeyOfArgArray( [ $_GET['unsafekey'] => 'safe' ] ); // Safe

'@taint-check-debug-method-first-arg VariadicFunctions::execKeyOfEachArg';
VariadicFunctions::execKeyOfEachArg( 'safe', $_GET['unsafe'] ); // Unsafe
VariadicFunctions::execKeyOfEachArg( [ 'somekey' => $_GET['unsafe'] ] ); // Safe
VariadicFunctions::execKeyOfEachArg( [ $_GET['unsafekey'] => 'safe' ] ); // Unsafe
