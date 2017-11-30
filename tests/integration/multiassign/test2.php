<?php

$globalFoo = '';
$globalBar = '';

class Foo {
	private $bar;
	private $bar2;

	public function setEvil() {
		global $globalFoo;
		$this->bar2 = $_GET['evil'];
		$globalFoo = $_GET['evil'];
		// Note: intentional lack of global decleration.
		$globalBar = $_GET['evil'];
	}

	public function echoThing() {
		// Should give warning
		echo $this->bar2;
	}

	public function setGood() {
		global $globalFoo, $globalBar;
		$this->bar2 = 'good';
		$globalFoo = 'good';
		$globalBar = 'good';
	}

	/**
	 * This should trigger a warning, since
	 * there is no garuntee that setGood()
	 * was called before this despite it being
	 * earlier in the source file
	 */
	public function echoThing() {
		global $globalFoo, $globalBar;
		// Should give warning
		echo $this->bar2;
		// Should give warning
		echo $globalFoo;
		// No warning
		echo $globalBar;
	}

	public function f1() {
		// At the moment, this triggers a warning
		// Because we are not smart enough to know
		// if any non-local functions have modified
		// $this->bar in between setting it to something good.
		$this->bar = $_GET['tainted'];
		$this->bar = 'ok';
		// Should give warning
		echo $this->bar;
	}
}

$f = new Foo;

$f->setEvil();
$f->echoThing();
