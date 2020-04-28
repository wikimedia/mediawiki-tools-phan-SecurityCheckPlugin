<?php

// Regression test for PassByReferenceVariable objects holding a Property.

class MyClass {
	public static $staticProp;
	public $prop;
	public function setAndPrint( &$x, $val ) {
		$x = $val;
		echo $x;
	}
}

$obj = new MyClass();
$obj->setAndPrint( MyClass::$staticProp, $_GET['baz'] );
$obj->setAndPrint( $obj->prop, $_GET['baz'] );
echo MyClass::$staticProp;
echo $obj->prop;
