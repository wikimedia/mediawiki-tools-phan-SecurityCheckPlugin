<?php

// Regression test for functions where different branches use different portions of the argument, but using the same
// key for both dimensions (here, "safe").

function testParamSinkError( $arg ) {
	if ( rand() ) {
		$x = $arg['safe'];
	} else {
		$x = $arg['safe']['safe'];
	}
	echo $x;
}
testParamSinkError( $_GET['a'] ); // Caused by 8, 10, 12
testParamSinkError( [ 'safe' => $_GET['a'] ] ); // Caused by 8, 10, 12
testParamSinkError( [ 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Caused by 8, 12 (and NOT 10)


function testParamPreserveError( $arg3 ) {
	if ( rand() ) {
		$ret = $arg3['safe'];
	} else {
		$ret = $arg3['safe']['safe'];
	}
	return $ret;
}
echo testParamPreserveError( $_GET['a'] ); // Caused by 21, 23, 25
echo testParamPreserveError( [ 'safe' => $_GET['a'] ] ); // Caused by 21, 23, 25
echo testParamPreserveError( [ 'safe' => [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ] ] ); // Caused by 21, 25 (and NOT 23)


function testOverallError() {
	$ret = [];
	if ( rand() ) {
		$ret['safe'] = $_GET['a'];
	} else {
		$ret['safe']['safe'] = $_GET['b'];
	}
	return $ret;
}
echo testOverallError(); // Caused by 35, 37, 39
echo testOverallError()['safe']; // Caused by 35, 37, 39
echo testOverallError()['safe']['safe']; // Caused by 35, 37, 39
echoSafe( testOverallError() ); // Caused by 35, 37, 39
echoSafeSafe( testOverallError() ); // Caused by 35, 37, 39

function echoSafe( $x ) {
	echo $x['safe'];
}
function echoSafeSafe( $x ) {
	echo $x['safe']['safe'];
}
