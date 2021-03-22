<?php

class TestPreserve {
	private $prop;

	public function setMyProp( $foo ) {
		$this->prop = $foo;
		$y = $this->prop;
		'@phan-debug-var-taintedness $y'; // Should NOT have PRESERVE
	}

	public function getPropIgnoreArg( $t ) {
		return $this->prop;
	}

	function main() {
		echo $this->getPropIgnoreArg( $_GET['a'] ); // Safe!
	}
}
