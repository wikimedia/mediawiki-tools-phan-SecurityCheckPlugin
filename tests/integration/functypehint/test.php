<?php

function getTaintedSafe(): int {
	return $_GET['a'];
}
echo getTaintedSafe(); // Safe

function getTaintedUnsafe(): string {
	return $_GET['a'];
}
echo getTaintedUnsafe(); // Unsafe

function passThroughSafe( $x ): int {
	return $x;
}
echo passThroughSafe( $_GET['a'] ); // Safe

function passThroughUnsafe( $x ): string {
	return $x;
}
echo passThroughUnsafe( $_GET['a'] ); // Unsafe

class SafeToString {
	public function __toString(): string {
		return 'safe';
	}

	static function getInstance(): self {
		return new self;
	}
}
echo SafeToString::getInstance(); // Safe

function passThroughSafeToString( $x ): SafeToString {
	return $x;
}
echo passThroughSafeToString( $_GET['a'] ); // Safe

class UnsafeToString {
	public function __toString(): string {
		return $_GET['x'];
	}

	static function getInstance(): self {
		return new self;
	}
}
echo UnsafeToString::getInstance(); // Unsafe

function passThroughUnsafeToString( $x ): UnsafeToString {
	return $x;
}
echo passThroughUnsafeToString( $_GET['a'] ); // Unsafe
