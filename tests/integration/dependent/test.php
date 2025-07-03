<?php

// Regression test for bug where if you set a semi-evil
// value, all taint types were being backpropagated instead of
// just the correct taints.

class DependentClass1 {
	private $somePrivVar;

	public function setProp( $val ) {
		$this->somePrivVar = $val;
	}

	public function output() {
		echo $this->somePrivVar;
	}
}
$a = new DependentClass1;
$a->setProp( htmlspecialchars( $_GET['foo'] ) );
