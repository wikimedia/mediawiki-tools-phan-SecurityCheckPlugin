<?php

function testSimple() {
	$arr = [
		$_GET['a'],
		'foo' => $_GET['b'],
		'baz' => [ 'inner' => $_GET['x'] ]
	];
	'@phan-debug-var-taintedness $arr';
	unset( $arr[42] ); // No change
	'@phan-debug-var-taintedness $arr';
	unset( $arr[0] );
	'@phan-debug-var-taintedness $arr';
	unset( $arr['foo'] );
	'@phan-debug-var-taintedness $arr';
	unset( $arr['baz'] );
	'@phan-debug-var-taintedness $arr';
}

function testUnknownKeys() {
	$arr = [
		$_GET['unknown'] => $_GET['a'],
		'foo' => 'safe',
	];
	'@phan-debug-var-taintedness $arr';
	unset( $arr[$_GET['also-unknown']] ); // No change
	'@phan-debug-var-taintedness $arr';
	unset( $arr['foo'] );
	'@phan-debug-var-taintedness $arr';
}

function testDepth() {
	$arr = [
		'inner' => [
			'safe' => 'safe',
			'unsafe' => $_GET['x'],
			'inner2' => [
				'safe2' => 'safe',
				'unsafe2' => $_GET['x']
			]
		],
	];
	'@phan-debug-var-taintedness $arr';
	unset( $arr['inner']['inner2']['unsafe2'] );
	'@phan-debug-var-taintedness $arr';
	unset( $arr['inner']['inner2'] );
	'@phan-debug-var-taintedness $arr';
	unset( $arr['inner']['unsafe'] );
	'@phan-debug-var-taintedness $arr';
	unset( $arr['inner'] );
	'@phan-debug-var-taintedness $arr';
}

class TestUnset {
	public $someProp;
}

function testWithFullElement() {
	$var = $_GET['x'];
	unset( $var );
	echo $var; // LikelyFalsePositive

	$classObj = new TestUnset();
	$classObj->someProp = $_GET['a'];
	echo $classObj->someProp; // XSS
	unset( $classObj->someProp );
	echo $classObj->someProp; // Ideally safe

	$stdObj = new stdClass();
	$stdObj->someProp = $_GET['a'];
	echo $stdObj->someProp; // Ideally XSS
	unset( $stdObj->someProp );
	echo $stdObj->someProp; // Safe
}