<?php

class TestGetterSetter {
	private $myProp;

	public function setter( $arg ) {
		$this->myProp = $arg;
	}

	public function getter() {
		return $this->myProp;
	}
}

$class = new TestGetterSetter;
$a = $_GET['foo'];
$class->setter( $a );
echo $class->getter();
