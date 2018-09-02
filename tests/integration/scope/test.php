<?php

$g1 = '';
$g2 = $_GET['evil'];

class Foo {
	public $m1;

	public function unsafe1() {
		$a = $_GET['evil'];
		if ( rand() ) {
			$a = '';
		}
		// unsafe
		echo $a;
	}

	public function safe1() {
		$a = $_GET['evil'];
		$a = '';
		// safe
		echo $a;
	}

	public function unsafe2() {
		$a = $_GET['evil'];
		if ( rand() ) {
			if ( rand() ) {
				if ( rand() ) {
					$a = '';
				}
			}
		}
		// unsafe
		echo $a;
	}

	public function unsafe3() {
		global $g1;
		$g1 = $_GET['evil'];
		// unsafe
		echo $g1;
	}

	public function unsafe4() {
		global $g2;
		$g2 = 'safe';
		doSomethingThatMaySetAGlobal();
		// unsafe
		echo $g2;
	}

	public function unsafe5() {
		global $g2;
		if ( rand() ) {
			$g2 = 'safe';
			doSomethingThatMaySetAGlobal();
		}
		// unsafe
		echo $g2;
	}

	public function unsafe6() {
		$this->m1 = $_GET['evil'];
		$this->m1 = 'safe';
		doSomethingThatMaySetAGlobal();
		// unsafe
		echo $this->m1;
	}

	public function unsafe7() {
		$this->m1 = $_GET['evil'];
		if ( rand() ) {
			$this->m1 = 'safe';
			doSomethingThatMaySetAGlobal();
		}
		// unsafe
		echo $this->m1;
	}
}

function doSomethingThatMaySetAGlobal() {
	global $g1;
	$g1 = 'faddfa';
}
