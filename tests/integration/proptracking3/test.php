<?php

class NonTrivialGetter {
	public $foo;
	public function getFoo() {
		$ret = $this->foo;
		return $ret;
	}
	public function setFoo($v) {
		$this->foo = $v;
	}
}

function doEcho( NonTrivialGetter $obj ) {
	echo $obj->getFoo();
}

function setEvil() {
	$obj = new NonTrivialGetter;
	$obj->setFoo( $_GET['x'] );
	doEcho( $obj ); // This is unsafe, but taint-check can't figure out the return at line 7
}
