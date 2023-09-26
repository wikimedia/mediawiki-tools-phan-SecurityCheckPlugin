<?php

// Regression test for a bug where MethodLinks::asValueFirstLevel was not cloning the links,
// and therefore leaking modifications.

// Trigger analysis
( new TestValueClone( 'foo' ) )->doTest();
new TestValueClone( $_GET['x'] ); // Safe. This would be reported as an XSS with the leak.

class TestValueClone {
	public $myProp;

	public function doTest() {
		foreach ( $this->myProp as $k => $_ ) {
			echo $k;
		}
	}

	public function __construct( $arg ) {
		$this->myProp = [ 'somekey' => $arg ];
	}
}
