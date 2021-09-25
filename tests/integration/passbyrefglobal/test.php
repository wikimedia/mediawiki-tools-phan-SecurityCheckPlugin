<?php

function alwaysTaint( &$arg ) {
	$arg = $_GET['baz'];
}

function maybeTaint( &$arg ) {
	if ( rand() ) {
		alwaysTaint( $arg );
	}
}

function echoAndTaint( &$arg ) {
	echo $arg;
	alwaysTaint( $arg );
}

function alwaysEscape( &$arg ) {
	$arg = htmlspecialchars( $arg );
}

function maybeEscape( &$arg ) {
	if ( rand() ) {
		alwaysEscape( $arg );
	}
}

function echoAndEscape( &$arg ) {
	echo $arg;
	$arg = htmlspecialchars( $arg );
}

function noop( &$arg ) {
}




$glob1 = '';
function test1() {
	global $glob1;
	alwaysTaint( $glob1 );
	echo $glob1; // Unsafe
}

$glob2 = '';
function test2() {
	global $glob2;
	maybeTaint( $glob2 );
	echo $glob2; // Unsafe
}

$glob3 = $_GET['x'];
function test3() {
	global $glob3;
	maybeEscape( $glob3 );
	echo $glob3; // Unsafe
}

$glob4 = $_GET['a'];
function test4() {
	global $glob4;
	noop( $glob4 );
	echo $glob4; // Unsafe
}

$glob5 = $_GET['b'];
function test5() {
	global $glob5;
	echoAndTaint( $glob5 ); // Unsafe
	echo $glob5; // Unsafe
}

$glob6 = '';
function test6() {
	global $glob6;
	echoAndTaint( $glob6 ); // Safe
	echo $glob6; // Unsafe
}

$glob7 = $_GET['foo'];
function test7() {
	global $glob7;
	echoAndEscape( $glob7 ); // Unsafe
	echo $glob7; // Ideally safe, but we never override taint on globals
}

$glob8 = $_GET['bar'];
function test8() {
	global $glob8;
	alwaysEscape( $glob8 );
	echo $glob8; // Ideally safe, but we never override taint on globals
}

$glob9 = '';
function test9() {
	global $glob9;
	alwaysTaint( $glob9 );
	alwaysEscape( $glob9 );
	echo $glob9; // Ideally safe, but we never override taint on globals
}
