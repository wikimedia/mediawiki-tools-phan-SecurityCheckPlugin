<?php

// Regression test for bug where if you set a semi-evil
// value, all taint types were being backpropagated instead of
// just the correct taints.

class Fred {
	private $somePrivVar;

	public function bar( $val ) {
		$this->somePrivVar = $val;
	}

	public function output() {
		echo $this->somePrivVar;
	}
}
$a = new Fred;
$a->bar( htmlspecialchars( $_GET['foo'] ) );
