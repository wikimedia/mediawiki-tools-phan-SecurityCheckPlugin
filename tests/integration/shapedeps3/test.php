<?php

function createDependencyGraph() {
	( new TestWhole( 'foo' ) )->doTest();
	( new TestEcho( 'foo' ) )->doTest();
	( new TestShell( 'foo' ) )->doTest();
	( new TestRequireWhole( 'foo' ) )->doTest();
	( new TestRequireSafe( 'foo' ) )->doTest();
	( new TestRequireUnsafe( 'foo' ) )->doTest();
	( new TestUnknown( 'foo' ) )->doTest();
	( new TestKeys( 'foo' ) )->doTest();
}

function doEvilStuff() {
	new TestWhole( $_GET['a'] ); // HTML, SHELL, PATH, SERIALIZE
	new TestEcho( $_GET['a'] ); // HTML
	new TestShell( $_GET['a'] ); // SHELL
	new TestRequireWhole( $_GET['a'] ); // PATH
	new TestRequireSafe( $_GET['a'] ); // Safe
	new TestRequireUnsafe( $_GET['a'] ); // PATH
	new TestUnknown( $_GET['a'] ); // HTML, SHELL, PATH
	new TestKeys( $_GET['a'] ); // SERIALIZE
}

class TestWhole {
	public $wholeProp;

	public function doTest() {
		allSink( $this->wholeProp ); // HTML, SHELL, PATH, SERIALIZE
	}

	public function __construct( $arg ) {
		$this->wholeProp = $arg;
	}
}

class TestEcho {
	public $echoProp;

	public function doTest() {
		allSink( $this->echoProp ); // TODO: HTML
	}

	public function __construct( $arg ) {
		$this->echoProp = [ 'echo' => $arg, 'shell' => 'safe', 'require' => [] ];
	}
}

class TestShell {
	public $shellProp;

	public function doTest() {
		allSink( $this->shellProp ); // TODO: SHELL
	}

	public function __construct( $arg ) {
		$this->shellProp = [ 'echo' => 'safe', 'shell' => $arg, 'require' => [] ];
	}
}

class TestRequireWhole {
	public $requireWholeProp;

	public function doTest() {
		allSink( $this->requireWholeProp ); // TODO: PATH
	}

	public function __construct( $arg ) {
		$this->requireWholeProp = [ 'echo' => 'safe', 'shell' => 'x', 'require' => $arg ];
	}
}

class TestRequireSafe {
	public $requireSafeProp;

	public function doTest() {
		allSink( $this->requireSafeProp ); // TODO Safe
	}

	public function __construct( $arg ) {
		$this->requireSafeProp = [ 'echo' => 'safe', 'shell' => 'x', 'require' => [ 'safe' => $arg, 'file' => 'safe' ] ];
	}
}

class TestRequireUnsafe {
	public $requireUnsafeProp;

	public function doTest() {
		allSink( $this->requireUnsafeProp );  // TODO: PATH
	}

	public function __construct( $arg ) {
		$this->requireUnsafeProp = [ 'echo' => 'safe', 'shell' => 'x', 'require' => [ 'file' => $arg ] ];
	}
}

class TestUnknown {
	public $unknownProp;

	public function doTest() {
		allSink( $this->unknownProp ); // HTML, SHELL, PATH
	}

	public function __construct( $arg ) {
		$this->unknownProp = [ $GLOBALS['unknown safe'] => $arg ];
	}
}

class TestKeys {
	public $keysProp;

	public function doTest() {
		allSink( $this->keysProp ); // SERIALIZE
	}

	public function __construct( $arg ) {
		$this->keysProp = [ $arg => 'safe' ];
	}
}

function allSink( $arg ) {
	echo $arg['echo'];
	shell_exec( $arg['shell'] );
	require $arg['require']['file'];
	foreach ( $arg as $k => $v ) {
		unserialize( $k );
	}
}
