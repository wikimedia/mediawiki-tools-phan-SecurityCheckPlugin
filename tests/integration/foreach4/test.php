<?php

/** @return-taint shell */
function getShell() {
	return $_GET['shell'];
}
/** @return-taint html */
function getHTML() {
	return $_GET['html'];
}

/** @return-taint sql */
function getSQL() {
	return $_GET['sql'];
}


function testDimValue1() {
	$arr = [ getHTML() ];
	$val = [ 'y' => getShell() ];
	foreach ( $arr as $val['x'] ) {
		'@phan-debug-var-taintedness $val';
	}
}

function testDimValue2() {
	$arr = [ getHTML() ];
	$val = [ 'x' => getShell() ];
	foreach ( $arr as $val['x'] ) {
		'@phan-debug-var-taintedness $val';
	}
}

function testDimValue3() {
	$arr = [ getHTML(), getShell() ];
	$val = [];
	foreach ( $arr as $val['x'] ) {
		'@phan-debug-var-taintedness $val';
	}
}


function testDimKey1() {
	$arr = [ getHTML() => 1 ];
	$val = [ 'y' => getShell() ];
	foreach ( $arr as $val['x'] => $_ ) {
		'@phan-debug-var-taintedness $val';
	}
}

function testDimKey2() {
	$arr = [ getHTML() => 1 ];
	$val = [ 'x' => getShell() ];
	foreach ( $arr as $val['x'] => $_ ) {
		'@phan-debug-var-taintedness $val';
	}
}

function testDimKey3() {
	$arr = [ getHTML() => 1, getShell() => 2 ];
	$val = [];
	foreach ( $arr as $val['x'] => $_ ) {
		'@phan-debug-var-taintedness $val';
	}
}


function testDimBoth1() {
	$arr = [ getHTML() => getSQL() ];
	$val = [ 'v' => getShell() ];
	foreach ( $arr as $val['k'] => $val['v'] ) {
		'@phan-debug-var-taintedness $val';
	}
}

function testDimBoth2() {
	$arr = [ getHTML() => getSQL() ];
	$val = [ 'k' => getShell() ];
	foreach ( $arr as $val['k'] => $val['v'] ) {
		'@phan-debug-var-taintedness $val';
	}
}

function testDimBoth3() {
	$arr = [ getHTML() => getShell(), getShell() => getSQL() ];
	$val = [];
	foreach ( $arr as $val['k'] => $val['v'] ) {
		'@phan-debug-var-taintedness $val';
	}
}

function testArrayValue1() {
	$arr = [ getHTML() ];
	foreach ( $arr as [ $v ] ) {// $v === getHTML()[0]
		'@phan-debug-var-taintedness $v';
	}
}

function testArrayValue2() {
	$arr = [ [ getHTML() ] ];
	foreach ( $arr as [ $v ] ) {// $v === getHTML()
		'@phan-debug-var-taintedness $v';
	}
}

function testArrayValue3() {
	$arr = [ [ [ 'x' => getHTML(), getShell() ] ] ];
	foreach ( $arr as [ $v ] ) {// $v === [ 'x' => getHTML(), getShell() ]
		'@phan-debug-var-taintedness $v';
	}
}
