<?php

$unsafe = $_GET['d']; // This must be part of the caused-by in the foreach

foreach ( $unsafe as $k => $v ) {
	echo $k; // Unsafe
	echo $v; // Unsafe
}
echo $k; // Unsafe
echo $v; // Unsafe

function inFunctionScope() {
	$unsafe = $_GET['d'];// This must be part of the caused-by in the foreach

	foreach ( $unsafe as $k => $v ) {
		echo $k; // Unsafe
		echo $v; // Unsafe
	}
}

function mutableForeach1() {
	$unsafe = $_GET['evil'];
	foreach ( $unsafe as &$value ) {
		echo $value; // Unsafe
	}
}

function mutableForeach2() {
	$arr = [ 'safe' ];
	foreach ( $arr as &$value ) {
		$value = $_GET['x'];
	}
	echo $value; // Unsafe
	echo $arr; // TODO: This is unsafe
}

class PropertyForeachKey {
	public $key;
	public $val;

	function testBase() {
		$unsafe = $_GET;
		foreach ( $unsafe as $this->key => $this->val ) {
			echo $this->key; // Unsafe
			echo $this->val; // Unsafe
		}
	}
	function testNoOverride() {
		$this->val = $this->key = $_GET['foo'];
		$safe = [ 'foo' ];
		foreach ( $safe as $this->key => $this->val ) {
			echo $this->key; // Still unsafe
			echo $this->val; // Still unsafe
		}
		echo $this->key; // Still unsafe
		echo $this->val; // Still unsafe
	}
}


$a = [ 'dog', 'cat' ];
$a[] = $_GET['goat']; // This must be part of the caused-by in the foreach

foreach ( $a as $animal ) {
	echo $animal; // Unsafe
}

$keys = [ $_GET['evil'] => 'foo' ]; // This must be part of the caused-by in the foreach
foreach ( $keys as $key => &$value ) {
	echo $key; // Unsafe
}

$info = $_GET['x'];
refForeach( $info );

function refForeach( &$info ) {
	foreach ( $info as &$child ) {// This would throw an exception due to PassByRef usage
	}
}

