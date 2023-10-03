<?php

// Helpers
function echoSafeShellUnsafe( $x ) {
	echo $x['safe'];
	shell_exec( $x['unsafe'] );
}

function echoAll( $x ) {
	echo $x;
}

function echoKeys( $x ) {
	foreach ( $x as $k => $v ) {
		echo $k;
	}
}

// Warm up
( new WrapInArray( 'foo' ) )->doSink();
( new WrapPartialSafeInArray( 'foo' ) )->doSink();
( new WrapPartialUnsafeInArray( 'foo' ) )->doSink();
( new PartialSafeOnly( 'foo' ) )->doSink();
( new PartialUnsafeOnly( 'foo' ) )->doSink();
( new WrapInArrayAtOffsetSafe( 'foo' ) )->doSink();
( new WrapInArrayAtOffsetUnsafe( 'foo' ) )->doSink();
( new WrapPartialInArrayAtOffsetSafe( 'foo' ) )->doSink();
( new WrapPartialInArrayAtOffsetUnsafe( 'foo' ) )->doSink();
( new PartialOnlyAtOffsetSafe( 'foo' ) )->doSink();
( new PartialOnlyAtOffsetUnsafe( 'foo' ) )->doSink();
( new PartialOnlyAtOffsetUnknown( 'foo' ) )->doSink();
( new SetAsKey( 'foo' ) )->doSink();
( new WrapEscaped( 'foo' ) )->doSink();

// The issues in the following lines come from the method links, the ones for each class are from var links

new WrapInArray( $_GET['a'] ); // Shell
new WrapPartialSafeInArray( [ 'safe' => 'safe', 'unsafe' => $_GET['a'] ] ); // Safe
new WrapPartialUnsafeInArray( [ 'safe' => 'safe', 'unsafe' => $_GET['a'] ] ); // Shell
new PartialSafeOnly( [ 'safe' => 'safe', 'unsafe' => $_GET['a'] ] ); // Safe
new PartialUnsafeOnly( [ 'safe' => 'safe', 'unsafe' => $_GET['a'] ] ); // XSS
new WrapInArrayAtOffsetSafe( $_GET['a'] ); // Safe
new WrapInArrayAtOffsetUnsafe( $_GET['a'] ); // Shell
new WrapPartialInArrayAtOffsetSafe( [ 'safe' => 'safe', 'foo' => $_GET['a'] ] ); // Safe
new WrapPartialInArrayAtOffsetUnsafe( [ 'safe' => 'safe', 'foo' => $_GET['a'] ] ); // Shell
new PartialOnlyAtOffsetSafe( [ 'safe' => 'safe', 'foo' => $_GET['a'] ] ); // Safe
new PartialOnlyAtOffsetUnsafe( [ 'safe' => 'safe', 'foo' => $_GET['a'] ] ); // XSS
new PartialOnlyAtOffsetUnknown( [ 'safe' => 'safe', 'foo' => $_GET['a'] ] ); // XSS
new SetAsKey( $_GET['a'] ); // XSS
new WrapEscaped( $_GET['a'] ); // Safe

class WrapInArray {
	private $myProp1;

	public function doSink() {
		echoSafeShellUnsafe( $this->myProp1 ); // Shell
	}

	public function __construct( $arg ) {
		$this->myProp1 = [ 'safe' => 'x', 'unsafe' => $arg ];
	}
}

class WrapPartialSafeInArray {
	private $myProp2;

	public function doSink() {
		echoSafeShellUnsafe( $this->myProp2 ); // Safe
	}

	public function __construct( $arg ) {
		$this->myProp2 = [ 'safe' => 'x', 'unsafe' => $arg['safe'] ];
	}
}

class WrapPartialUnsafeInArray {
	private $myProp3;

	public function doSink() {
		echoSafeShellUnsafe( $this->myProp3 ); // Shell
	}

	public function __construct( $arg ) {
		$this->myProp3 = [ 'safe' => 'x', 'unsafe' => $arg['unsafe'] ];
	}
}

class PartialSafeOnly {
	private $myProp4;

	public function doSink() {
		echoAll( $this->myProp4 ); // Safe
	}

	public function __construct( $arg ) {
		$this->myProp4 = $arg['safe'];
	}
}

class PartialUnsafeOnly {
	private $myProp5;

	public function doSink() {
		echoAll( $this->myProp5 ); // XSS
	}

	public function __construct( $arg ) {
		$this->myProp5 = $arg['unsafe'];
	}
}

class WrapInArrayAtOffsetSafe {
	private $myProp6;

	public function doSink() {
		echoSafeShellUnsafe( $this->myProp6['foo'] ); // Safe
	}

	public function __construct( $arg ) {
		$this->myProp6['foo'] = 'safe!';
		$this->myProp6['baz'] = [ 'safe' => 'x', 'unsafe' => $arg ];
	}
}

class WrapInArrayAtOffsetUnsafe {
	private $myProp7;

	public function doSink() {
		echoSafeShellUnsafe( $this->myProp7['foo'] ); // Shell
	}

	public function __construct( $arg ) {
		$this->myProp7['foo'] = [ 'safe' => 'x', 'unsafe' => $arg ];
		$this->myProp7['baz'] = 'safe!';
	}
}

class WrapPartialInArrayAtOffsetSafe {
	private $myProp8;

	public function doSink() {
		echoSafeShellUnsafe( $this->myProp8['foo'] ); // Safe
	}

	public function __construct( $arg ) {
		$this->myProp8['foo'] = 'safe';
		$this->myProp8['baz'] = [ 'safe' => 'x', 'unsafe' => $arg['foo'] ];
	}
}

class WrapPartialInArrayAtOffsetUnsafe {
	private $myProp9;

	public function doSink() {
		echoSafeShellUnsafe( $this->myProp9['foo'] ); // Shell
	}

	public function __construct( $arg ) {
		$this->myProp9['foo'] = [ 'safe' => 'x', 'unsafe' => $arg['foo'] ];
		$this->myProp9['baz'] = 'safe';
	}
}

class PartialOnlyAtOffsetSafe {
	private $myProp10;

	public function doSink() {
		echoAll( $this->myProp10['foo'] ); // Safe
	}

	public function __construct( $arg ) {
		$this->myProp10['baz'] = $arg['foo'];
		$this->myProp10['foo'] = 'safe';
	}
}

class PartialOnlyAtOffsetUnsafe {
	private $myProp11;

	public function doSink() {
		echoAll( $this->myProp11['baz'] ); // XSS
	}

	public function __construct( $arg ) {
		$this->myProp11['baz'] = $arg['foo'];
		$this->myProp11['foo'] = 'safe';
	}
}

class PartialOnlyAtOffsetUnknown {
	private $myProp12;

	public function doSink() {
		echoAll( $this->myProp12[$GLOBALS['unknown']] ); // XSS
	}

	public function __construct( $arg ) {
		$this->myProp12['baz'] = $arg['foo'];
		$this->myProp12['foo'] = 'safe';
	}
}

class SetAsKey {
	private $myProp13;

	public function doSink() {
		echoAll( $this->myProp13 ); // XSS
	}

	public function __construct( $arg ) {
		$this->myProp13[$arg] = 'safe';
	}
}

class WrapEscaped {
	private $myProp14;

	public function doSink() {
		echoSafeShellUnsafe( $this->myProp14 ); // Safe
	}

	public function __construct( $arg ) {
		$this->myProp14 = [ 'safe' => htmlspecialchars( $arg ), 'unsafe' => 'safe' ];
	}
}
