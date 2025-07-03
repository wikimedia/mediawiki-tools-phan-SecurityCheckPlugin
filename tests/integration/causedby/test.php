<?php

// In global scope
$res2 = $_GET['unsafe']; // Unsafe, but should not be in caused-by because taintedness is cleared by the next line
$res2 = 'somethingsafe';
$res2 = $_GET['unsafe2']; // This line in the only source of taintedness

echo $res2;

function inFunctionScope() {
	$res2 = $_GET['unsafe']; // Unsafe, but should not be in caused-by because taintedness is cleared by the next line
	$res2 = 'somethingsafe';
	$res2 = $_GET['unsafe2']; // This line in the only source of taintedness

	echo $res2;
}

class CausedByClassScope {
	private $foo;
	public function setEvil1() {
		$this->foo = $_GET['baz']; // This shouldn't be in caused-by, but we're not smart enough yet
	}
	public function setEvil2() {
		$this->foo = $_GET['foo'];
	}
	public function main() {
		$this->setEvil1();
		$this->setEvil2();
		echo $this->foo;
	}
}

$globalFoo = '';

function doStuff() {
	global $globalFoo;

	$globalFoo = $_GET['baz']; // This shouldn't be in caused-by, but we're not smart enough yet
	$globalFoo = $_GET['foo'];
	echo $globalFoo;
}
