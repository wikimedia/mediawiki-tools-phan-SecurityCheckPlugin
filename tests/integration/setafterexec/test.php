<?php

// All in the global scope
$var1 = 'foo';
echoArg( $var1 ); // Safe
$var1 = $_GET['x'];

// Set safe in global scope, do everything else inside functions
$var2 = 'foo';
function execVar2() {
	global $var2;
	// TODO Should probably flag this... But then we might get a false positive for line 5...
	echoArg( $var2 );
}
function setVar2() {
	global $var2;
	$var2 = $_GET['x'];
}

// Set unsafe in global scope, do everything else inside functions
function setVar3() {
	global $var3;
	$var3 = 'safe';
}
function execVar3() {
	global $var3;
	echoArg( $var3 );
}
$var3 = $_GET['x'];

// All in functions
function initVar4() {
	global $var4;
	$var4 = 'safe';
}
function execVar4() {
	global $var4;
	echoArg( $var4 );
}
function taintVar4() {
	global $var4;
	$var4 = $_GET['x'];
}

class TestClassPropsSetAfterExec {
	private $myProp;
	private function __construct() {
		$this->myProp = 'safe';
	}
	private function execProp() {
		echoArg( $this->myProp );
	}
	private function taintProp() {
		$this->myProp = $_GET['x'];
	}
}













function echoArg( $x ) {
	echo $x;
}
