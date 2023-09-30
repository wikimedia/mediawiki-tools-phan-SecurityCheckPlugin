<?php

/*
 * Test for redundant caused-by lines involving properties. Note that sink classes should be declared before
 * trigger classes, but the latter should be used before both definitions for this bug to be reproducible.
 */

/** @param-taint $x exec_html */
function mySink( $x ) {
}

$triggerClass = new MyTriggerClass();
$triggerClass->triggerProp = $_GET['a'];
$triggerClass->doTrigger();

class MySinkClass {
	public $sinkProp;

	public function __construct( string $arg ) {
		$this->sinkProp = $arg;
		mySink( $this->sinkProp ); // Unsafe, but this is not the point of the test.
	}
}

class MyTriggerClass {
	public $triggerProp = '';

	public function doTrigger() {
		new MySinkClass( $this->triggerProp ); // Unsafe. The caused-by lines for the sink part must NOT contain line 29 and 13 (13 must be in the arg part).
	}
}

$localTriggerClass = new MyTriggerClassWithLocalVar();
$localTriggerClass->triggerWithLocalVar();

class MySinkClassForLocalVar {
	public $sinkProp2;

	public function __construct( string $arg2 ) {
		$this->sinkProp2 = $arg2;
		mySink( $this->sinkProp2 ); // Unsafe, but this is not the point of the test.
	}
}

class MyTriggerClassWithLocalVar {
	function triggerWithLocalVar() {
		$x = $_GET['a'];
		new MySinkClassForLocalVar( $x );// Unsafe. The caused-by lines for the sink part must NOT contain lines 48 and 47 (47 must be in the arg part).
	}
}

