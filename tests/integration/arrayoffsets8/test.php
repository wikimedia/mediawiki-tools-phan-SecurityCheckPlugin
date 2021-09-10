<?php

/** @return-taint shell */
function getShell() {
	return 'x';
}
/** @return-taint html */
function getHTML() {
	return 'x';
}


function test1() {
	$arr = [ 0 => getShell() ];
	$arr[1] = getHTML();
	'@phan-debug-var-taintedness $arr';
}

function test2() {
	$arr = [ 1 => getShell() ];
	$arr[1] = getHTML();
	'@phan-debug-var-taintedness $arr';
}
function test3() {
	$arr = [ 0 => getShell(), 1 => [ 0 => getHTML() ] ];
	$arr[1][2] = getShell();
	'@phan-debug-var-taintedness $arr';
}
