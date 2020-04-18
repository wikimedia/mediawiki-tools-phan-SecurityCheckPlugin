<?php

class TrivialGetter {
	public $foo;
	public function getFoo() {
		return $this->foo;
	}
	public function setFoo($v) {
		$this->foo = $v;
	}
}

function doEcho( TrivialGetter $obj ) {
	echo $obj->getFoo();
}

function setEvil() {
	$obj = new TrivialGetter;
	$obj->setFoo( $_GET['x'] );
	doEcho( $obj );
}
