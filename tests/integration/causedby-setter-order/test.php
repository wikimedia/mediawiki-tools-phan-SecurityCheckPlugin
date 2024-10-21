<?php

MyRC::newWithSetter( 'foo' );
MyRC::newWithSetterUnsafe();
( new MyRC() )->printProp();

class MyRC {
	/** @var array */
	public $prop = [];

	public static function newWithSetter( $val ) {
		$obj = new self;
		$obj->setProp( $val );

		return $obj;
	}

	public static function newWithSetterUnsafe() {
		$unsafe = $_GET['a'];
		return self::newWithSetter( $unsafe ); // Unsafe, sink caused by 13, 29, 24, 25 (in this order)
	}

	function printProp() {
		$propVal = $this->prop;
		echo $propVal;
	}

	public function setProp( $val ) {
		$this->prop = $val;
	}
}