<?php

class MyClass {
	public static $prop;
	public function setAndPrint( &$x, $val ) {
		$x = $val;
		echo $x;
	}
}

$x = new MyClass();
$x->setAndPrint( MyClass::$prop, $_GET['baz'] );
echo MyClass::$prop;
