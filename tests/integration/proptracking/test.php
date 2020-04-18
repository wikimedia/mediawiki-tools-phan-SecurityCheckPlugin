<?php

class Returned {
	public $foo;
	public function getFoo() {
		return $this->foo;
	}
	public function setFoo($v) {
		$this->foo = $v;
	}
}

$obj = new Returned();
$obj->setFoo( $_GET['x'] );
echo $obj->getFoo();

class Echoed {
	public $foo;
	public function getFoo() {
		return $this->foo;
	}
	public function setFoo($v) {
		$this->foo = $v;
	}
	public function echoFoo() {
		echo $this->getFoo();
	}
}

$obj = new Echoed();
$obj->setFoo( $_GET['x'] );
$obj->echoFoo();
