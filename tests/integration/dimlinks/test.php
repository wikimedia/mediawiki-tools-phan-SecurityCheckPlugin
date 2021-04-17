<?php

function echoParam( $param ) {
	$x = [];
	$x['foo'] = $param;
	echo $x['foo'];
}
echoParam( $_GET['a'] ); // Unsafe

function echoParamIndirect( $param ) {
	$x = [];
	$x['foo'] = $param;
	$y = $x['foo'];
	echo $y;
}
echoParamIndirect( $_GET['a'] ); // Unsafe

function echoParamLiteral( $param ) {
	$x = [ 'foo' => $param ];
	echo $x['foo'];
}
echoParamLiteral( $_GET['a'] ); // Unsafe

function echoParamLiteralIndirect( $param ) {
	$x = [ 'foo' => $param ];
	$y = $x['foo'];
	echo $y;
}
echoParamLiteralIndirect( $_GET['a'] ); // Unsafe

function echoSafe( $param ) {
	$x = [];
	$x['foo'] = $param;
	echo $x['bar'];
}
echoSafe( $_GET['a'] ); // Safe

function echoSafeIndirect( $param ) {
	$x = [];
	$x['foo'] = $param;
	$y = $x['bar'];
	echo $y;
}
echoSafeIndirect( $_GET['a'] ); // Safe

function echoSafeLiteral( $param ) {
	$x = [ 'foo' => $param ];
	echo $x['bar'];
}
echoSafeLiteral( $_GET['a'] ); // Safe

function echoSafeLiteralIndirect( $param ) {
	$x = [ 'foo' => $param ];
	$y = $x['bar'];
	echo $y;
}
echoSafeLiteralIndirect( $_GET['a'] ); // Safe

function echoSafe2( $param ) {
	$x = [];
	$x['foo'] = $param;
	$x['bar'] = 'safe';
	echo $x['bar'];
}
echoSafe2( $_GET['a'] ); // Safe

function echoSafeIndirect2( $param ) {
	$x = [];
	$x['foo'] = $param;
	$x['bar'] = 'safe';
	$y = $x['bar'];
	echo $y;
}
echoSafeIndirect2( $_GET['a'] ); // Safe

function echoSafeLiteral2( $param ) {
	$x = [ 'foo' => $param, 'bar' => 'safe' ];
	echo $x['bar'];
}
echoSafeLiteral2( $_GET['a'] ); // Safe

function echoSafeLiteralIndirect2( $param ) {
	$x = [ 'foo' => $param, 'bar' => 'safe' ];
	$y = $x['bar'];
	echo $y;
}
echoSafeLiteralIndirect2( $_GET['a'] ); // Safe
