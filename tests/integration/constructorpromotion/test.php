<?php

class SomeClass {
	public function __construct(
		public $nodefault,
		public $unsafe = 'default',
		public $safe = 'safe'
	) {
	}
}

$safeClass = new SomeClass( 'safe' );
echo $safeClass->nodefault; // Safe
echo $safeClass->safe; // Safe
echo $safeClass->unsafe; // Safe (because taint-check still hasn't seen the unsafe value below)

$unsafeClass = new SomeClass( 'safe', $_GET['foo'] ); // Unsafe, due to the echo above
echo $unsafeClass->nodefault; // Safe
echo $unsafeClass->safe; // Safe
echo $unsafeClass->unsafe; // Unsafe

$anotherSafeClass = new SomeClass( 'safe' );
echo $anotherSafeClass->nodefault; // Safe
echo $anotherSafeClass->safe; // Safe
echo $anotherSafeClass->unsafe; // Ideally safe, but unsafe because we don't track properties per-instance

$anotherUnsafeClass = new SomeClass( $_GET['x'], $_GET['y'] ); // Unsafe due to both properties being echoed above
echo $anotherUnsafeClass->nodefault; // Unsafe
echo $anotherUnsafeClass->safe; // Safe
echo $anotherUnsafeClass->unsafe; // Unsafe
