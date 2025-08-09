<?php

function test1() {
	$obj = getUnsafeStdClass();
	echo $obj; // Unsafe
	echo $obj->foo; // Unsafe
	$fieldName = getFieldName();
	echo $obj->$fieldName; // Unsafe
}

function test2() {
	$obj = (object)[ 'a' => $_GET['foo'] ];
	echo $obj; // Unsafe
	echo $obj->a; // Unsafe
	$fieldName = getFieldName();
	echo $obj->$fieldName; // Unsafe
}

function test3() {
	$obj = (object)[ 'a' => 'foo' ];
	echo $obj; // Safe
	echo $obj->a; // Safe
	$fieldName = getFieldName();
	echo $obj->$fieldName; // Safe
}

function test4() {
	$obj = (object)[ 'a' => 'foo', 'b' => $_GET['unsafe'] ];
	echo $obj; // Unsafe
	echo $obj->a; // TODO Safe
	echo $obj->b; // Unsafe
	$fieldName = getFieldName();
	echo $obj->$fieldName; // Unsafe
}

class TestStdClass {
	private $prop1, $prop2, $prop3, $prop4;

	function test1() {
		$this->prop1 = getUnsafeStdClass();
		echo $this->prop1; // Unsafe
		echo $this->prop1->foo; // Unsafe
		$fieldName = getFieldName();
		echo $this->prop1->$fieldName; // Unsafe
	}

	function test2() {
		$this->prop2 = (object)[ 'a' => $_GET['foo'] ];
		echo $this->prop2; // Unsafe
		echo $this->prop2->a; // Unsafe
		$fieldName = getFieldName();
		echo $this->prop2->$fieldName; // Unsafe
	}

	function test3() {
		$this->prop3 = (object)[ 'a' => 'foo' ];
		echo $this->prop3; // Safe
		echo $this->prop3->a; // Safe
		$fieldName = getFieldName();
		echo $this->prop3->$fieldName; // Safe
	}

	function test4() {
		$this->prop4 = (object)[ 'a' => 'foo', 'b' => $_GET['unsafe'] ];
		echo $this->prop4; // Unsafe
		echo $this->prop4->a; // TODO Safe
		echo $this->prop4->b; // Unsafe
		$fieldName = getFieldName();
		echo $this->prop4->$fieldName; // Unsafe
	}
}
