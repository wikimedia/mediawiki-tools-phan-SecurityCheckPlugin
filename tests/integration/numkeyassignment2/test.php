<?php

function getTaintedString(): string {
	return $_GET['a'];
}

function getTaintedArray(): array {
	return $_GET['a'];
}

function testNumkeyUnknown() {
	$var = [ $_GET['x'] ];
	'@phan-debug-var-taintedness $var';
}
function testNumkeyString() {
	$var = [ getTaintedString() ];
	'@phan-debug-var-taintedness $var';
}
function testNumkeyArray() {
	$var = [ getTaintedArray() ];
	'@phan-debug-var-taintedness $var';// TODO This should probably have numkey somewhere
}
