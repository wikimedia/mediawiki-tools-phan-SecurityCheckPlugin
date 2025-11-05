<?php

namespace TestAllowOverride;

/**
 * @return-taint none
 */
function returnTaintNone() {
	return $_GET['evil'];
}

/**
 * @return-taint none, allow_override
 */
function returnTaintNone__AllowOverride() {
	return $_GET['evil'];
}

/**
 * Testing default value for return-taint
 * @param-taint $a none
 */
function noReturnTaint( $a ) {
	return $_GET['evil'];
}

/**
 * @param-taint $a none
 */
function paramTaintNone( $a ) {
	echo $a;
}

/**
 * @param-taint $a none, allow_override
 */
function paramTaintNone__AllowOverride( $a ) {
	echo $a;
}

/**
 * Test unspecified parameters
 * @param-taint $a none
 */
function noParamTaint( $a, $b ) {
	echo $b;
}

// safe
echo returnTaintNone();
// unsafe
echo returnTaintNone__AllowOverride();
// unsafe
echo noReturnTaint( 'd' );
// safe
paramTaintNone( $_GET[ 'evil'] );
// unsafe
paramTaintNone__AllowOverride( $_GET[ 'evil'] );
// unsafe
noParamTaint( $_GET['evil1'], $_GET[ 'evil'] );
