<?php

use Wikimedia\Rdbms\Database;

/**
 * @return-taint none
 */
function getFieldName(): string {
	return $_GET['unknown'];
}

function test1() {
	$row = ( new Database )->selectRow( 'a', 'b' );
	echo $row; // Unsafe
	echo $row->foo; // Unsafe
	$fieldName = getFieldName();
	echo $row->$fieldName; // Unsafe
}

function test2() {
	$row = (object)[ 'a' => $_GET['foo'] ];
	echo $row; // Unsafe
	echo $row->a; // Unsafe
	$fieldName = getFieldName();
	echo $row->$fieldName; // Unsafe
}

function test3() {
	$row = (object)[ 'a' => 'foo' ];
	echo $row; // Safe
	echo $row->a; // Safe
	$fieldName = getFieldName();
	echo $row->$fieldName; // Safe
}

function test4() {
	$row = (object)[ 'a' => 'foo', 'b' => $_GET['unsafe'] ];
	echo $row; // Unsafe
	echo $row->a; // TODO Safe
	echo $row->b; // Unsafe
	$fieldName = getFieldName();
	echo $row->$fieldName; // Unsafe
}

class TestDbRow {
	private $prop1, $prop2, $prop3, $prop4;

	function test1() {
		$this->prop1 = ( new Database )->selectRow( 'a', 'b' );
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
