<?php

/** @return-taint none */
function getUnknown() {
	return rand() ? 42 : 'foobar';//Make sure to use a non-numeric string
}

function testSimple() {
	$var = [ $_GET['a'] ];
	'@phan-debug-var-taintedness $var';
}
function testNested() {
	$var = [ [ $_GET['a'] ] ];
	'@phan-debug-var-taintedness $var';
}
function testIntIndex() {
	$var = [];
	$var[1] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testStringIndex() {
	$var = [];
	$var['foo'] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testNumericStringIndex() {
	$var = [];
	$var['1'] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testImplicitIndex() {
	$var = [];
	$var[] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testUnknownIndex() {
	$var = [];
	$var[getUnknown()] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testAppendToString() {
	$var = [];
	$var['foo'][] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testAppendToInt() {
	$var = [];
	$var[1][] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testAppendToUnknown() {
	$var = [];
	$var[getUnknown()][] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}

function testAppendIntToString() {
	$var = [];
	$var['foo'][1] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testAppendStringToString() {
	$var = [];
	$var['foo']['bar'] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testAppendIntToInt() {
	$var = [];
	$var[1][1] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}
function testAppendStringToInt() {
	$var = [];
	$var[1]['foo'] = $_GET['a'];
	'@phan-debug-var-taintedness $var';
}

function testIntIndexArray() {
	$var = [];
	$var[1] = [ $_GET['a'] ];
	'@phan-debug-var-taintedness $var';
}
function testStringIndexArray() {
	$var = [];
	$var['foo'] = [ $_GET['a'] ];
	'@phan-debug-var-taintedness $var';
}
function testNumericStringIndexArray() {
	$var = [];
	$var['1'] = [ $_GET['a'] ];
	'@phan-debug-var-taintedness $var';
}
function testImplicitIndexArray() {
	$var = [];
	$var[] = [ $_GET['a'] ];
	'@phan-debug-var-taintedness $var';
}
function testUnknownIndexArray() {
	$var = [];
	$var[getUnknown()] = [ $_GET['a'] ];
	'@phan-debug-var-taintedness $var';
}

function testInnerMixed1() {
	$var = [ $_GET['a'], [ $_GET['b'] ] ];
	'@phan-debug-var-taintedness $var';
}
function testInnerMixed2() {
	$var = [ [ $_GET['a'] ], $_GET['b'] ];
	'@phan-debug-var-taintedness $var';
}

function testSimpleNumericString() {
	$var = [ '1' => $_GET['a'] ];
	'@phan-debug-var-taintedness $var';
}
function testSimpleUnknown() {
	$var = [ getUnknown() => $_GET['a'] ];
	'@phan-debug-var-taintedness $var';
}
