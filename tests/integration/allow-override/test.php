<?php

/**
 * @return-taint none
 */
function foo1() {
	return $_GET['evil'];
}

/**
 * @return-taint none, allow_override
 */
function foo2() {
	return $_GET['evil'];
}

/**
 * Testing default value for return-taint
 * @param-taint $a none
 */
function foo3( $a ) {
	return $_GET['evil'];
}

/**
 * @param-taint $a none
 */
function foo4( $a ) {
	echo $a;
}

/**
 * @param-taint $a none, allow_override
 */
function foo5( $a ) {
	echo $a;
}

/**
 * Test unspecified parameters
 * @param-taint $a none
 */
function foo6( $a, $b ) {
	echo $b;
}

// safe
echo foo1();
// unsafe
echo foo2();
// unsafe
echo foo3( 'd' );
// safe
foo4( $_GET[ 'evil'] );
// unsafe
foo5( $_GET[ 'evil'] );
// unsafe
foo6( $_GET['evil1'], $_GET[ 'evil'] );
