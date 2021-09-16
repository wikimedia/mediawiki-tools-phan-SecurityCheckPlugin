<?php

function createDependencyGraph() {
	( new TestBackpropCausedBy( 'foo' ) )->echoProp();
}

function doTest() {
	new TestBackpropCausedBy( $_GET['x'] ); // XSS, see below for expected caused-by
}

class TestBackpropCausedBy {
	public $prop;

	public function echoProp() {
		echoAll( $this->prop, 'safe' ); // @phan-suppress-current-line SecurityCheck-XSS
	}
	public function __construct( $arg ) {
		$this->prop = $arg;
	}
}

function echoAll( $arg1, $arg2 ) {
	echo $arg1; // This should be the only line in the caused-by
	$x = $_GET['a'];
	echo $x; // @phan-suppress-current-line SecurityCheck-XSS This one shouldn't be in the caused-by lines
	echo $_GET['b']; // @phan-suppress-current-line SecurityCheck-XSS This one shouldn't be in the caused-by lines
	echo $arg2; // This one shouldn't be in the caused-by lines
	$y = $arg2;
	echo $y; // This one shouldn't be in the caused-by lines
}
