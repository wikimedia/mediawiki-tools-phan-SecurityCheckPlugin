<?php

function alwaysTaint( &$arg ) {
	$arg = $_GET['baz'];
}
function maybeTaint( &$arg ) {
	if ( rand() ) {
		alwaysTaint( $arg );
	}
}
function echoAndTaint( &$arg ) {
	echo $arg;
	alwaysTaint( $arg );
}
function alwaysEscape( &$arg ) {
	$arg = htmlspecialchars( $arg );
}
function maybeEscape( &$arg ) {
	if ( rand() ) {
		alwaysEscape( $arg );
	}
}
function echoAndEscape( &$arg ) {
	echo $arg;
	$arg = htmlspecialchars( $arg );
}
function noop( &$arg ) {
}

// Lines commented as "Ideally safe" mean that the line in question is practically safe, but we
// consider it unsafe because taint is never overridden for props.

class Foo {
	public $myProp;

	function test1() {
		$this->myProp = '';
		alwaysTaint( $this->myProp );
		echo $this->myProp; // Unsafe
	}

	function test2() {
		$this->myProp = '';
		maybeTaint( $this->myProp );
		echo $this->myProp; // Unsafe
	}

	function test3() {
		$this->myProp = $_GET['baz'];
		maybeEscape( $this->myProp );
		echo $this->myProp; // Unsafe
	}

	function test4() {
		$this->myProp = $_GET['baz'];
		noop( $this->myProp );
		echo $this->myProp; // Unsafe
	}

	function test5() {
		$this->myProp = $_GET['baz'];
		echoAndTaint( $this->myProp ); // Unsafe
		echo $this->myProp; // Unsafe
	}

	function test6() {
		$this->myProp = '';
		echoAndTaint( $this->myProp ); // Ideally safe
		echo $this->myProp; // Unsafe
	}

	function test7() {
		$this->myProp = $_GET['baz'];
		echoAndEscape( $this->myProp ); // Unsafe
		echo $this->myProp; // Ideally safe
	}

	function test8() {
		$this->myProp = $_GET['baz'];
		alwaysEscape( $this->myProp ); // Ideally safe
		echo $this->myProp; // Ideally safe
	}

	function test9() {
		alwaysTaint( $this->myProp );
		alwaysEscape( $this->myProp ); // Ideally safe
		echo $this->myProp; // Ideally safe
	}

	function test10() {
		$this->myProp = '';
		echoAndEscape( $this->myProp ); // Ideally safe
		echo $this->myProp; // Ideally safe
	}
}
