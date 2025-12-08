<?php

// For T249519

class TestUndeclaredThis2 {
	protected function mainFunc() {
		$obj = new TestUndeclaredThis2Helper;
		htmlspecialchars( $obj->getProp() );
	}
}

class TestUndeclaredThis2Helper {
	private $prop;

	public function getProp(): string {
		return $this->prop; // No "Variable $this is undeclared" here
	}
}

