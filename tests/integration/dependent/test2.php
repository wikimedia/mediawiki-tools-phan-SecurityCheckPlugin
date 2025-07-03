<?php

// Regression test for bug where if you set a semi-evil
// value, all taint types were being backpropagated instead of
// just the correct taints.

class DependentClass2 {
	private $somePrivVar;

	public function setProp( $val ) {
		$this->somePrivVar = $val;// This must be in the caused-by lines
	}

	public function output() {
		echo htmlspecialchars( $this->somePrivVar );
	}
}
$a = new DependentClass2;
$a->setProp( htmlspecialchars( $_GET['foo'] ) );
