<?php

/** @return-taint html */
function getHTML() {
	return $_GET['html'];
}

/** @return-taint shell */
function getShell() {
	return $_GET['shell'];
}

/** @return-taint sql */
function getSQL() {
	return $_GET['sql'];
}

function testSimpleAssignment() {
	$res = $a = [ 'foo' => $_GET['a'] ];
	'@phan-debug-var-taintedness $res';
}

function testOffsetAssignment() {
	$res = $a['x'] = [ 'foo' => $_GET['a'] ];
	'@phan-debug-var-taintedness $res';
}

function testSimpleAssignmentReplace() {
	$a = [ 'bar' => getHTML() ];
	$res = $a = [ 'foo' => getShell() ];
	'@phan-debug-var-taintedness $res';
}

function testOffsetAssignmentMerge() {
	$a = [ 'bar' => getHTML() ];
	$res = $a['foo'] = getShell();
	'@phan-debug-var-taintedness $res';
}

function testOffsetAssignmentReplace() {
	$a = [ 'foo' => getHTML() ];
	$res = $a['foo'] = getShell();
	'@phan-debug-var-taintedness $res';
}

function testOffsetAssignmentAdd() {
	$a = [ 'foo' => [ 'bar' => getHTML() ] ];
	$res = $a['foo'] += [ 'baz' => getShell() ];
	'@phan-debug-var-taintedness $res';
}

function testArrayAssignment() {
	$res = [ $a, $b ] = [ getHTML(), getShell() ];
	'@phan-debug-var-taintedness $res';
}

function testArrayAssignmentOffsetMerge() {
	$a = [ 'foo' => getShell() ];
	$res = [ $a['bar'], $b ] = [ getHTML(), getShell() ];
	'@phan-debug-var-taintedness $res';
}

function testArrayAssignmentOffsetReplace() {
	$a = [ 'foo' => getShell() ];
	$res = [ $a['foo'], $b ] = [ getHTML(), getShell() ];
	'@phan-debug-var-taintedness $res';
}





function testSimpleAssignmentNumkey() {
	$res = $a = [ $_GET['a'] ];
	'@phan-debug-var-taintedness $res';
}

function testOffsetAssignmentNumkey() {
	$res = $a[1] = $_GET['a'];
	'@phan-debug-var-taintedness $res';
}

function testSimpleAssignmentReplaceNumkey() {
	$a = [ 0 => getHTML() ];
	$res = $a = [ 1 => getSQL() ];
	'@phan-debug-var-taintedness $res';
}

function testOffsetAssignmentMergeNumkey() {
	$a = [ 0 => getHTML() ];
	$res = $a[1] = getSQL();
	'@phan-debug-var-taintedness $res';
}

function testOffsetAssignmentReplaceNumkey() {
	$a = [ 0 => getHTML() ];
	$res = $a[0] = getSQL();
	'@phan-debug-var-taintedness $res';
}

function testOffsetAssignmentAddNumkey() {
	$a = [ 0 => [ 1 => getHTML() ] ];
	$res = $a[0] += [ 2 => getSQL() ];
	'@phan-debug-var-taintedness $res';
}

function testArrayAssignmentOffsetMergeNumkey() {
	$a = [ 0 => getShell() ];
	$res = [ $a[1], $b ] = [ getHTML(), getSQL() ];
	'@phan-debug-var-taintedness $res';
}

function testArrayAssignmentOffsetReplaceNumkey() {
	$a = [ 0 => getShell() ];
	$res = [ $a[0], $b ] = [ getHTML(), getSQL() ];
	'@phan-debug-var-taintedness $res';
}
