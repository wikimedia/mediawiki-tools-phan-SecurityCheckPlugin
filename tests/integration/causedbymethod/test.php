<?php

class CausedByMethod {
	public function getData() {
		return $this->getEvil();
	}

	public function getEvil() {
		return $_GET['a'];
	}
}

$test = new CausedByMethod();
echo $test->getData();// Should have line 9 in its caused-by
