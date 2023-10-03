<?php

// First and second param whole

function firstParamWholeSecondWhole_echoAllPathUnknown( $first, $second ) {
	$sinkArg = $first . $second;
	echoAllPathUnknown( $sinkArg );
}
firstParamWholeSecondWhole_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondWhole_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondWhole_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondWhole_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondWhole_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondWhole_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second foo

function firstParamWholeSecondFoo_echoAllPathUnknown( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['foo'] = $second;
	echoAllPathUnknown( $sinkArg );
}
firstParamWholeSecondFoo_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondFoo_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondFoo_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondFoo_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondFoo_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondFoo_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondFoo_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondFoo_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second bar

function firstParamWholeSecondBar_echoAllPathUnknown( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['bar'] = $second;
	echoAllPathUnknown( $sinkArg );
}
firstParamWholeSecondBar_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondBar_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondBar_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondBar_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondBar_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondBar_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondBar_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondBar_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondBar_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second unknown

function firstParamWholeSecondUnknown_echoAllPathUnknown( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[rand()] = $second;
	echoAllPathUnknown( $sinkArg );
}
firstParamWholeSecondUnknown_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondUnknown_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondUnknown_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondUnknown_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondUnknown_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondUnknown_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondUnknown_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondUnknown_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second key

function firstParamWholeSecondKey_echoAllPathUnknown( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[$second] = 'foo';
	echoAllPathUnknown( $sinkArg );
}
firstParamWholeSecondKey_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondKey_echoAllPathUnknown( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondKey_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondKey_echoAllPathUnknown( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondKey_echoAllPathUnknown( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondKey_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondKey_echoAllPathUnknown( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondKey_echoAllPathUnknown( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondKey_echoAllPathUnknown( getPath(), getPath() ); // PATH 1

// First and second param foo

function firstParamFooSecondFoo_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ 'foo' => $first . $second ];
	echoAllPathUnknown( $sinkArg );
}
firstParamFooSecondFoo_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamFooSecondFoo_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondFoo_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamFooSecondFoo_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamFooSecondFoo_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondFoo_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamFooSecondFoo_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondFoo_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param foo, second bar

function firstParamFooSecondBar_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ 'foo' => $first, 'bar' => $second ];
	echoAllPathUnknown( $sinkArg );
}
firstParamFooSecondBar_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamFooSecondBar_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondBar_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamFooSecondBar_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamFooSecondBar_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondBar_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondBar_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamFooSecondBar_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondBar_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param foo, second unknown

function firstParamFooSecondUnknown_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ 'foo' => $first, rand() => $second ];
	echoAllPathUnknown( $sinkArg );
}
firstParamFooSecondUnknown_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamFooSecondUnknown_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondUnknown_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamFooSecondUnknown_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamFooSecondUnknown_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondUnknown_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamFooSecondUnknown_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondUnknown_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param foo, second key

function firstParamFooSecondKey_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ 'foo' => $first, $second => 'safe' ];
	echoAllPathUnknown( $sinkArg );
}
firstParamFooSecondKey_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1
firstParamFooSecondKey_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondKey_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamFooSecondKey_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondKey_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondKey_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1
firstParamFooSecondKey_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1
firstParamFooSecondKey_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondKey_echoAllPathUnknown( getPath(), getPath() ); // PATH 1

// First and second param bar

function firstParamBarSecondBar_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ 'bar' => $first . $second ];
	echoAllPathUnknown( $sinkArg );
}
firstParamBarSecondBar_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamBarSecondBar_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamBarSecondBar_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamBarSecondBar_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamBarSecondBar_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamBarSecondBar_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamBarSecondBar_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamBarSecondBar_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param bar, second unknown

function firstParamBarSecondUnknown_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ 'bar' => $first, rand() => $second ];
	echoAllPathUnknown( $sinkArg );
}
firstParamBarSecondUnknown_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamBarSecondUnknown_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamBarSecondUnknown_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamBarSecondUnknown_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamBarSecondUnknown_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondUnknown_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamBarSecondUnknown_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamBarSecondUnknown_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamBarSecondUnknown_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param bar, second key

function firstParamBarSecondKey_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ 'bar' => $first, $second => 'safe' ];
	echoAllPathUnknown( $sinkArg );
}
firstParamBarSecondKey_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1
firstParamBarSecondKey_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamBarSecondKey_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamBarSecondKey_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondKey_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondKey_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1
firstParamBarSecondKey_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1
firstParamBarSecondKey_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamBarSecondKey_echoAllPathUnknown( getPath(), getPath() ); // PATH 1

// First and second param unknown

function firstParamUnknownSecondUnknown_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ rand() => $first, rand() => $second ];
	echoAllPathUnknown( $sinkArg );
}
firstParamUnknownSecondUnknown_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamUnknownSecondUnknown_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamUnknownSecondUnknown_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamUnknownSecondUnknown_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamUnknownSecondUnknown_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondUnknown_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamUnknownSecondUnknown_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamUnknownSecondUnknown_echoAllPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param unknown, second key

function firstParamUnknownSecondKey_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ rand() => $first, $second => 'safe' ];
	echoAllPathUnknown( $sinkArg );
}
firstParamUnknownSecondKey_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1
firstParamUnknownSecondKey_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamUnknownSecondKey_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamUnknownSecondKey_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamUnknownSecondKey_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondKey_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1
firstParamUnknownSecondKey_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1
firstParamUnknownSecondKey_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamUnknownSecondKey_echoAllPathUnknown( getPath(), getPath() ); // PATH 1

// First and second param key

function firstParamKeySecondKey_echoAllPathUnknown( $first, $second ) {
	$sinkArg = [ $first => 'safe', $second => 'safe' ];
	echoAllPathUnknown( $sinkArg );
}
firstParamKeySecondKey_echoAllPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathUnknown( $_GET['a'], getPath() ); // HTML 1
firstParamKeySecondKey_echoAllPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathUnknown( getHTML(), getPath() ); // HTML 1
firstParamKeySecondKey_echoAllPathUnknown( getPath(), $_GET['b'] ); // HTML 2
firstParamKeySecondKey_echoAllPathUnknown( getPath(), getHTML() ); // HTML 2
firstParamKeySecondKey_echoAllPathUnknown( getPath(), getPath() ); // Safe
