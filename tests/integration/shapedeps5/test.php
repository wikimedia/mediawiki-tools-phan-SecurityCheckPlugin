<?php

// First and second param whole

function firstParamWholeSecondWhole_echoAllPathFooKey( $first, $second ) {
	$sinkArg = $first . $second;
	echoAllPathFooKey( $sinkArg );
}
firstParamWholeSecondWhole_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondWhole_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondWhole_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondWhole_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondWhole_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondWhole_echoAllPathFooKey( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second foo

function firstParamWholeSecondFoo_echoAllPathFooKey( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['foo'] = $second;
	echoAllPathFooKey( $sinkArg );
}
firstParamWholeSecondFoo_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondFoo_echoAllPathFooKey( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoAllPathFooKey( $_GET['a'], getPath() ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondFoo_echoAllPathFooKey( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2, PATH 2
firstParamWholeSecondFoo_echoAllPathFooKey( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondFoo_echoAllPathFooKey( getHTML(), getPath() ); // TODO HTML 1, PATH 2
firstParamWholeSecondFoo_echoAllPathFooKey( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1 & 2
firstParamWholeSecondFoo_echoAllPathFooKey( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondFoo_echoAllPathFooKey( getPath(), getPath() ); // TODO PATH 1 & 2

// First param whole, second bar

function firstParamWholeSecondBar_echoAllPathFooKey( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['bar'] = $second;
	echoAllPathFooKey( $sinkArg );
}
firstParamWholeSecondBar_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondBar_echoAllPathFooKey( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondBar_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondBar_echoAllPathFooKey( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondBar_echoAllPathFooKey( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondBar_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondBar_echoAllPathFooKey( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondBar_echoAllPathFooKey( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondBar_echoAllPathFooKey( getPath(), getPath() ); // PATH 1

// First param whole, second unknown

function firstParamWholeSecondUnknown_echoAllPathFooKey( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[rand()] = $second;
	echoAllPathFooKey( $sinkArg );
}
firstParamWholeSecondUnknown_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondUnknown_echoAllPathFooKey( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoAllPathFooKey( $_GET['a'], getPath() ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondUnknown_echoAllPathFooKey( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2, PATH 2
firstParamWholeSecondUnknown_echoAllPathFooKey( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondUnknown_echoAllPathFooKey( getHTML(), getPath() ); // TODO HTML 1, PATH 2
firstParamWholeSecondUnknown_echoAllPathFooKey( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1 & 2
firstParamWholeSecondUnknown_echoAllPathFooKey( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondUnknown_echoAllPathFooKey( getPath(), getPath() ); // TODO PATH 1 & 2

// First param whole, second key

function firstParamWholeSecondKey_echoAllPathFooKey( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[$second] = 'foo';
	echoAllPathFooKey( $sinkArg );
}
firstParamWholeSecondKey_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondKey_echoAllPathFooKey( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondKey_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondKey_echoAllPathFooKey( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondKey_echoAllPathFooKey( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondKey_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondKey_echoAllPathFooKey( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondKey_echoAllPathFooKey( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondKey_echoAllPathFooKey( getPath(), getPath() ); // PATH 1

// First and second param foo

function firstParamFooSecondFoo_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ 'foo' => $first . $second ];
	echoAllPathFooKey( $sinkArg );
}
firstParamFooSecondFoo_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamFooSecondFoo_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondFoo_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamFooSecondFoo_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamFooSecondFoo_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondFoo_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamFooSecondFoo_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondFoo_echoAllPathFooKey( getPath(), getPath() ); // PATH 1 & 2

// First param foo, second bar

function firstParamFooSecondBar_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ 'foo' => $first, 'bar' => $second ];
	echoAllPathFooKey( $sinkArg );
}
firstParamFooSecondBar_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1
firstParamFooSecondBar_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondBar_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamFooSecondBar_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondBar_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondBar_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1
firstParamFooSecondBar_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2, PATH 1
firstParamFooSecondBar_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondBar_echoAllPathFooKey( getPath(), getPath() ); // PATH 1

// First param foo, second unknown

function firstParamFooSecondUnknown_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ 'foo' => $first, rand() => $second ];
	echoAllPathFooKey( $sinkArg );
}
firstParamFooSecondUnknown_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamFooSecondUnknown_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondUnknown_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamFooSecondUnknown_echoAllPathFooKey( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2, PATH 1 & 2
firstParamFooSecondUnknown_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondUnknown_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamFooSecondUnknown_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondUnknown_echoAllPathFooKey( getPath(), getPath() ); // PATH 1 & 2

// First param foo, second key

function firstParamFooSecondKey_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ 'foo' => $first, $second => 'safe' ];
	echoAllPathFooKey( $sinkArg );
}
firstParamFooSecondKey_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1
firstParamFooSecondKey_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondKey_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamFooSecondKey_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondKey_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondKey_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1
firstParamFooSecondKey_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2, PATH 1
firstParamFooSecondKey_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondKey_echoAllPathFooKey( getPath(), getPath() ); // PATH 1

// First and second param bar

function firstParamBarSecondBar_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ 'bar' => $first . $second ];
	echoAllPathFooKey( $sinkArg );
}
firstParamBarSecondBar_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1
firstParamBarSecondBar_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1
firstParamBarSecondBar_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2
firstParamBarSecondBar_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2
firstParamBarSecondBar_echoAllPathFooKey( getPath(), getPath() ); // Safe

// First param bar, second unknown

function firstParamBarSecondUnknown_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ 'bar' => $first, rand() => $second ];
	echoAllPathFooKey( $sinkArg );
}
firstParamBarSecondUnknown_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamBarSecondUnknown_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamBarSecondUnknown_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamBarSecondUnknown_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamBarSecondUnknown_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondUnknown_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamBarSecondUnknown_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2, PATH 2
firstParamBarSecondUnknown_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2
firstParamBarSecondUnknown_echoAllPathFooKey( getPath(), getPath() ); // PATH 2

// First param bar, second key

function firstParamBarSecondKey_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ 'bar' => $first, $second => 'safe' ];
	echoAllPathFooKey( $sinkArg );
}
firstParamBarSecondKey_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondKey_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamBarSecondKey_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1
firstParamBarSecondKey_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondKey_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondKey_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1
firstParamBarSecondKey_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2
firstParamBarSecondKey_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2
firstParamBarSecondKey_echoAllPathFooKey( getPath(), getPath() ); // Safe

// First and second param unknown

function firstParamUnknownSecondUnknown_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ rand() => $first, rand() => $second ];
	echoAllPathFooKey( $sinkArg );
}
firstParamUnknownSecondUnknown_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamUnknownSecondUnknown_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamUnknownSecondUnknown_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamUnknownSecondUnknown_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamUnknownSecondUnknown_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondUnknown_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamUnknownSecondUnknown_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamUnknownSecondUnknown_echoAllPathFooKey( getPath(), getPath() ); // PATH 1 & 2

// First param unknown, second key

function firstParamUnknownSecondKey_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ rand() => $first, $second => 'safe' ];
	echoAllPathFooKey( $sinkArg );
}
firstParamUnknownSecondKey_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1
firstParamUnknownSecondKey_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamUnknownSecondKey_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamUnknownSecondKey_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamUnknownSecondKey_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondKey_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1
firstParamUnknownSecondKey_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2, PATH 1
firstParamUnknownSecondKey_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamUnknownSecondKey_echoAllPathFooKey( getPath(), getPath() ); // PATH 1

// First and second param key

function firstParamKeySecondKey_echoAllPathFooKey( $first, $second ) {
	$sinkArg = [ $first => 'safe', $second => 'safe' ];
	echoAllPathFooKey( $sinkArg );
}
firstParamKeySecondKey_echoAllPathFooKey( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathFooKey( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathFooKey( $_GET['a'], getPath() ); // HTML 1
firstParamKeySecondKey_echoAllPathFooKey( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathFooKey( getHTML(), getHTML() ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathFooKey( getHTML(), getPath() ); // HTML 1
firstParamKeySecondKey_echoAllPathFooKey( getPath(), $_GET['b'] ); // HTML 2
firstParamKeySecondKey_echoAllPathFooKey( getPath(), getHTML() ); // HTML 2
firstParamKeySecondKey_echoAllPathFooKey( getPath(), getPath() ); // Safe
