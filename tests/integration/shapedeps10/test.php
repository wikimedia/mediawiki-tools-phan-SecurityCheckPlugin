<?php

// First and second param whole

function firstParamWholeSecondWhole_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = $first . $second;
	echoUnknownPathKeys( $sinkArg );
}
firstParamWholeSecondWhole_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondWhole_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondWhole_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondWhole_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondWhole_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondWhole_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondWhole_echoUnknownPathKeys( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondWhole_echoUnknownPathKeys( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondWhole_echoUnknownPathKeys( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second foo

function firstParamWholeSecondFoo_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['foo'] = $second;
	echoUnknownPathKeys( $sinkArg );
}
firstParamWholeSecondFoo_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoUnknownPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondFoo_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondFoo_echoUnknownPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondFoo_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondFoo_echoUnknownPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondFoo_echoUnknownPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondFoo_echoUnknownPathKeys( getPath(), getPath() ); // PATH 1

// First param whole, second bar

function firstParamWholeSecondBar_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['bar'] = $second;
	echoUnknownPathKeys( $sinkArg );
}
firstParamWholeSecondBar_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondBar_echoUnknownPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondBar_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondBar_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondBar_echoUnknownPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondBar_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondBar_echoUnknownPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondBar_echoUnknownPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondBar_echoUnknownPathKeys( getPath(), getPath() ); // PATH 1

// First param whole, second unknown

function firstParamWholeSecondUnknown_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[rand()] = $second;
	echoUnknownPathKeys( $sinkArg );
}
firstParamWholeSecondUnknown_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoUnknownPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondUnknown_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondUnknown_echoUnknownPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondUnknown_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondUnknown_echoUnknownPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondUnknown_echoUnknownPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondUnknown_echoUnknownPathKeys( getPath(), getPath() ); // PATH 1

// First param whole, second key

function firstParamWholeSecondKey_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[$second] = 'foo';
	echoUnknownPathKeys( $sinkArg );
}
firstParamWholeSecondKey_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondKey_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1, PATH 1
firstParamWholeSecondKey_echoUnknownPathKeys( $_GET['a'], getPath() ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondKey_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1, PATH 2
firstParamWholeSecondKey_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamWholeSecondKey_echoUnknownPathKeys( getHTML(), getPath() ); // TODO HTML 1, PATH 2
firstParamWholeSecondKey_echoUnknownPathKeys( getPath(), $_GET['b'] ); // TODO PATH 1 & 2
firstParamWholeSecondKey_echoUnknownPathKeys( getPath(), getHTML() ); // PATH 1
firstParamWholeSecondKey_echoUnknownPathKeys( getPath(), getPath() ); // TODO PATH 1 & 2

// First and second param foo

function firstParamFooSecondFoo_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first . $second ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamFooSecondFoo_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondFoo_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondFoo_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondFoo_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondFoo_echoUnknownPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamFooSecondFoo_echoUnknownPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondFoo_echoUnknownPathKeys( getPath(), getPath() ); // Safe

// First param foo, second bar

function firstParamFooSecondBar_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, 'bar' => $second ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamFooSecondBar_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondBar_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondBar_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondBar_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondBar_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondBar_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondBar_echoUnknownPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamFooSecondBar_echoUnknownPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondBar_echoUnknownPathKeys( getPath(), getPath() ); // Safe

// First param foo, second unknown

function firstParamFooSecondUnknown_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, rand() => $second ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamFooSecondUnknown_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondUnknown_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondUnknown_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondUnknown_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondUnknown_echoUnknownPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamFooSecondUnknown_echoUnknownPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondUnknown_echoUnknownPathKeys( getPath(), getPath() ); // Safe

// First param foo, second key

function firstParamFooSecondKey_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, $second => 'safe' ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamFooSecondKey_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 2
firstParamFooSecondKey_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1
firstParamFooSecondKey_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamFooSecondKey_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1, PATH 2
firstParamFooSecondKey_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamFooSecondKey_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondKey_echoUnknownPathKeys( getPath(), $_GET['b'] ); // PATH 2
firstParamFooSecondKey_echoUnknownPathKeys( getPath(), getHTML() ); // Safe
firstParamFooSecondKey_echoUnknownPathKeys( getPath(), getPath() ); // PATH 2

// First and second param bar

function firstParamBarSecondBar_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first . $second ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamBarSecondBar_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondBar_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamBarSecondBar_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamBarSecondBar_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondBar_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondBar_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamBarSecondBar_echoUnknownPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamBarSecondBar_echoUnknownPathKeys( getPath(), getHTML() ); // HTML 2
firstParamBarSecondBar_echoUnknownPathKeys( getPath(), getPath() ); // Safe

// First param bar, second unknown

function firstParamBarSecondUnknown_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first, rand() => $second ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamBarSecondUnknown_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondUnknown_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamBarSecondUnknown_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamBarSecondUnknown_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondUnknown_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondUnknown_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamBarSecondUnknown_echoUnknownPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamBarSecondUnknown_echoUnknownPathKeys( getPath(), getHTML() ); // HTML 2
firstParamBarSecondUnknown_echoUnknownPathKeys( getPath(), getPath() ); // Safe

// First param bar, second key

function firstParamBarSecondKey_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first, $second => 'safe' ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamBarSecondKey_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 2
firstParamBarSecondKey_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1
firstParamBarSecondKey_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamBarSecondKey_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1, PATH 2
firstParamBarSecondKey_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamBarSecondKey_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamBarSecondKey_echoUnknownPathKeys( getPath(), $_GET['b'] ); // PATH 2
firstParamBarSecondKey_echoUnknownPathKeys( getPath(), getHTML() ); // Safe
firstParamBarSecondKey_echoUnknownPathKeys( getPath(), getPath() ); // PATH 2

// First and second param unknown

function firstParamUnknownSecondUnknown_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ rand() => $first, rand() => $second ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamUnknownSecondUnknown_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamUnknownSecondUnknown_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1
firstParamUnknownSecondUnknown_echoUnknownPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamUnknownSecondUnknown_echoUnknownPathKeys( getPath(), getHTML() ); // HTML 2
firstParamUnknownSecondUnknown_echoUnknownPathKeys( getPath(), getPath() ); // Safe

// First param unknown, second key

function firstParamUnknownSecondKey_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ rand() => $first, $second => 'safe' ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamUnknownSecondKey_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoUnknownPathKeys( $_GET['a'], getHTML() ); // HTML 1
firstParamUnknownSecondKey_echoUnknownPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoUnknownPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamUnknownSecondKey_echoUnknownPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoUnknownPathKeys( getPath(), $_GET['b'] ); // PATH 2
firstParamUnknownSecondKey_echoUnknownPathKeys( getPath(), getHTML() ); // Safe
firstParamUnknownSecondKey_echoUnknownPathKeys( getPath(), getPath() ); // PATH 2

// First and second param key

function firstParamKeySecondKey_echoUnknownPathKeys( $first, $second ) {
	$sinkArg = [ $first => 'safe', $second => 'safe' ];
	echoUnknownPathKeys( $sinkArg );
}
firstParamKeySecondKey_echoUnknownPathKeys( $_GET['a'], $_GET['b'] ); // PATH 1 & 2
firstParamKeySecondKey_echoUnknownPathKeys( $_GET['a'], getHTML() ); // PATH 1
firstParamKeySecondKey_echoUnknownPathKeys( $_GET['a'], getPath() ); // PATH 1 & 2
firstParamKeySecondKey_echoUnknownPathKeys( getHTML(), $_GET['b'] ); // PATH 2
firstParamKeySecondKey_echoUnknownPathKeys( getHTML(), getHTML() ); // Safe
firstParamKeySecondKey_echoUnknownPathKeys( getHTML(), getPath() ); // PATH 2
firstParamKeySecondKey_echoUnknownPathKeys( getPath(), $_GET['b'] ); // PATH 1 & 2
firstParamKeySecondKey_echoUnknownPathKeys( getPath(), getHTML() ); // PATH 1
firstParamKeySecondKey_echoUnknownPathKeys( getPath(), getPath() ); // PATH 1 & 2
