<?php

// Naming scheme: Test<where we put $arg><what is echoed>

function createDependencyGraph() {
	( new TestWholeWhole( 'foo' ) )->testWhole();
	( new TestWholeSafe( 'foo' ) )->testSafe();
	( new TestWholeUnsafe( 'foo' ) )->testUnsafe();
	( new TestWholeUnknown( 'foo' ) )->testUnknown();
	( new TestWholeKeys( 'foo' ) )->testKeys();
	( new TestSafeWhole( 'foo' ) )->testWhole();
	( new TestSafeSafe( 'foo' ) )->testSafe();
	( new TestSafeUnsafe( 'foo' ) )->testUnsafe();
	( new TestSafeUnknown( 'foo' ) )->testUnknown();
	( new TestSafeKeys( 'foo' ) )->testKeys();
	( new TestUnsafeWhole( 'foo' ) )->testWhole();
	( new TestUnsafeSafe( 'foo' ) )->testSafe();
	( new TestUnsafeUnsafe( 'foo' ) )->testUnsafe();
	( new TestUnsafeUnknown( 'foo' ) )->testUnknown();
	( new TestUnsafeKeys( 'foo' ) )->testKeys();
	( new TestUnknownWhole( 'foo' ) )->testWhole();
	( new TestUnknownSafe( 'foo' ) )->testSafe();
	( new TestUnknownUnsafe( 'foo' ) )->testUnsafe();
	( new TestUnknownUnknown( 'foo' ) )->testUnknown();
	( new TestUnknownKeys( 'foo' ) )->testKeys();
	( new TestKeysWhole( 'foo' ) )->testWhole();
	( new TestKeysSafe( 'foo' ) )->testSafe();
	( new TestKeysUnsafe( 'foo' ) )->testUnsafe();
	( new TestKeysUnknown( 'foo' ) )->testUnknown();
	( new TestKeysKeys( 'foo' ) )->testKeys();
}

function doEvilStuff() {
	new TestWholeWhole( $_GET['x'] ); // Unsafe
	new TestWholeSafe( $_GET['x'] ); // Unsafe
	new TestWholeUnsafe( $_GET['x'] ); // Unsafe
	new TestWholeUnknown( $_GET['x'] ); // Unsafe
	new TestWholeKeys( $_GET['x'] ); // Unsafe
	new TestSafeWhole( $_GET['x'] ); // Unsafe
	new TestSafeSafe( $_GET['x'] ); // Unsafe
	new TestSafeUnsafe( $_GET['x'] ); // Safe (although echoing the props is unsafe)
	new TestSafeUnknown( $_GET['x'] ); // Unsafe
	new TestSafeKeys( $_GET['x'] ); // TODO Safe
	new TestUnsafeWhole( $_GET['x'] ); // Unsafe
	new TestUnsafeSafe( $_GET['x'] ); // Safe
	new TestUnsafeUnsafe( $_GET['x'] ); // Unsafe
	new TestUnsafeUnknown( $_GET['x'] ); // Unsafe
	new TestUnsafeKeys( $_GET['x'] ); // TODO Safe
	new TestUnknownWhole( $_GET['x'] ); // Unsafe
	new TestUnknownSafe( $_GET['x'] ); // Unsafe
	new TestUnknownUnsafe( $_GET['x'] ); // Unsafe
	new TestUnknownUnknown( $_GET['x'] ); // Unsafe
	new TestUnknownKeys( $_GET['x'] ); // TODO Safe
	new TestKeysWhole( $_GET['x'] ); // Unsafe
	new TestKeysSafe( $_GET['x'] ); // TODO Safe
	new TestKeysUnsafe( $_GET['x'] ); // TODO Safe
	new TestKeysUnknown( $_GET['x'] ); // TODO Safe
	new TestKeysKeys( $_GET['x'] ); // Unsafe
}

class TestWholeWhole {
	public $propWholeWhole;

	public function testWhole() {
		echoWhole( $this->propWholeWhole ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propWholeWhole = $arg;
	}
}
class TestWholeSafe {
	public $propWholeSafe;

	public function testSafe() {
		echoSafe( $this->propWholeSafe ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propWholeSafe = $arg;
	}
}
class TestWholeUnsafe {
	public $propWholeUnsafe;

	public function testUnsafe() {
		echoUnsafe( $this->propWholeUnsafe ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propWholeUnsafe = $arg;
	}
}
class TestWholeUnknown {
	public $propWholeUnknown;

	public function testUnknown() {
		echoUnknown( $this->propWholeUnknown ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propWholeUnknown = $arg;
	}
}
class TestWholeKeys {
	public $propWholeKeys;

	public function testKeys() {
		echoKeys( $this->propWholeKeys ); // Unsafe
	}

	public function __construct( $arg ) {
		$this->propWholeKeys = $arg;
	}
}

class TestSafeWhole {
	public $propSafeWhole;

	public function testWhole() {
		echoWhole( $this->propSafeWhole ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propSafeWhole = [ 'safe' => $arg, 'unsafe' => $_GET['a'] ];
	}
}
class TestSafeSafe {
	public $propSafeSafe;

	public function testSafe() {
		echoSafe( $this->propSafeSafe ); // TODO Safe
	}
	public function __construct( $arg ) {
		$this->propSafeSafe = [ 'safe' => $arg, 'unsafe' => $_GET['a'] ];
	}
}
class TestSafeUnsafe {
	public $propSafeUnsafe;

	public function testUnsafe() {
		echoUnsafe( $this->propSafeUnsafe ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propSafeUnsafe = [ 'safe' => $arg, 'unsafe' => $_GET['a'] ];
	}
}
class TestSafeUnknown {
	public $propSafeUnknown;

	public function testUnknown() {
		echoUnknown( $this->propSafeUnknown ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propSafeUnknown = [ 'safe' => $arg, 'unsafe' => $_GET['a'] ];
	}
}
class TestSafeKeys {
	public $propSafeKeys;

	public function testKeys() {
		echoKeys( $this->propSafeKeys ); // TODO Safe
	}

	public function __construct( $arg ) {
		$this->propSafeKeys = [ 'safe' => $arg, 'unsafe' => $_GET['a'] ];
	}
}

class TestUnsafeWhole {
	public $propUnsafeWhole;

	public function testWhole() {
		echoWhole( $this->propUnsafeWhole ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propUnsafeWhole = [ 'unsafe' => $arg, 'safe' => 'safe' ];
	}
}
class TestUnsafeSafe {
	public $propUnsafeSafe;

	public function testSafe() {
		echoSafe( $this->propUnsafeSafe ); // TODO Safe
	}
	public function __construct( $arg ) {
		$this->propUnsafeSafe = [ 'unsafe' => $arg, 'safe' => 'safe' ];
	}
}
class TestUnsafeUnsafe {
	public $propUnsafeUnsafe;

	public function testUnsafe() {
		echoUnsafe( $this->propUnsafeUnsafe ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propUnsafeUnsafe = [ 'unsafe' => $arg, 'safe' => 'safe' ];
	}
}
class TestUnsafeUnknown {
	public $propUnsafeUnknown;

	public function testUnknown() {
		echoUnknown( $this->propUnsafeUnknown ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propUnsafeUnknown = [ 'unsafe' => $arg, 'safe' => 'safe' ];
	}
}
class TestUnsafeKeys {
	public $propUnsafeKeys;

	public function testKeys() {
		echoKeys( $this->propUnsafeKeys ); // TODO Safe
	}

	public function __construct( $arg ) {
		$this->propUnsafeKeys = [ 'unsafe' => $arg, 'safe' => 'safe' ];
	}
}

class TestUnknownWhole {
	public $propUnknownWhole;

	public function testWhole() {
		echoWhole( $this->propUnknownWhole ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propUnknownWhole = [ getUnknown() => $arg ];
	}
}
class TestUnknownSafe {
	public $propUnknownSafe;

	public function testSafe() {
		echoSafe( $this->propUnknownSafe ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propUnknownSafe = [ getUnknown() => $arg ];
	}
}
class TestUnknownUnsafe {
	public $propUnknownUnsafe;

	public function testUnsafe() {
		echoUnsafe( $this->propUnknownUnsafe ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propUnknownUnsafe = [ getUnknown() => $arg ];
	}
}
class TestUnknownUnknown {
	public $propUnknownUnknown;

	public function testUnknown() {
		echoUnknown( $this->propUnknownUnknown ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propUnknownUnknown = [ getUnknown() => $arg ];
	}
}
class TestUnknownKeys {
	public $propUnknownKeys;

	public function testKeys() {
		echoKeys( $this->propUnknownKeys ); // TODO Safe
	}

	public function __construct( $arg ) {
		$this->propUnknownKeys = [ getUnknown() => $arg ];
	}
}

class TestKeysWhole {
	public $propKeysWhole;

	public function testWhole() {
		echoWhole( $this->propKeysWhole ); // Unsafe
	}
	public function __construct( $arg ) {
		$this->propKeysWhole = [ $arg => 'safe' ];
	}
}
class TestKeysSafe {
	public $propKeysSafe;

	public function testSafe() {
		echoSafe( $this->propKeysSafe ); // TODO Safe
	}
	public function __construct( $arg ) {
		$this->propKeysSafe = [ $arg => 'safe' ];
	}
}
class TestKeysUnsafe {
	public $propKeysUnsafe;

	public function testUnsafe() {
		echoUnsafe( $this->propKeysUnsafe ); // TODO Safe
	}
	public function __construct( $arg ) {
		$this->propKeysUnsafe = [ $arg => 'safe' ];
	}
}
class TestKeysUnknown {
	public $propKeysUnknown;

	public function testUnknown() {
		echoUnknown( $this->propKeysUnknown ); // TODO Safe
	}
	public function __construct( $arg ) {
		$this->propKeysUnknown = [ $arg => 'safe' ];
	}
}
class TestKeysKeys {
	public $propKeysKeys;

	public function testKeys() {
		echoKeys( $this->propKeysKeys ); // Unsafe
	}

	public function __construct( $arg ) {
		$this->propKeysKeys = [ $arg => 'safe' ];
	}
}



function echoWhole( $arg ) {
	echo $arg;
}

function echoSafe( $arg ) {
	echo $arg['safe'];
}

function echoUnsafe( $arg ) {
	echo $arg['unsafe'];
}

function echoUnknown( $arg ) {
	echo $arg[getUnknown()];
}

function echoKeys( $arg ) {
	foreach ( $arg as $k => $_ ) {
		echo $k;
	}
}

/**
 * Helper to get an unknown type but without taint
 * @return-taint none
 */
function getUnknown() {
	return $GLOBALS['unknown'];
}
