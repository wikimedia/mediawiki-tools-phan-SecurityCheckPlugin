<?php

// First and second param whole

function firstParamWholeSecondWhole_echoAllPathKeys( $first, $second ) {
	$sinkArg = $first . $second;
	echoAllPathKeys( $sinkArg );
}
firstParamWholeSecondWhole_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondWhole_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondWhole_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondWhole_echoAllPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondWhole_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondWhole_echoAllPathKeys( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondWhole_echoAllPathKeys( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second foo

function firstParamWholeSecondFoo_echoAllPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['foo'] = $second;
	echoAllPathKeys( $sinkArg );
}
firstParamWholeSecondFoo_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoAllPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondFoo_echoAllPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondFoo_echoAllPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondFoo_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondFoo_echoAllPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondFoo_echoAllPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondFoo_echoAllPathKeys( getPath(), getPath() ); // PATH 1

// First param whole, second bar

function firstParamWholeSecondBar_echoAllPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['bar'] = $second;
	echoAllPathKeys( $sinkArg );
}
firstParamWholeSecondBar_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondBar_echoAllPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondBar_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondBar_echoAllPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondBar_echoAllPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondBar_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondBar_echoAllPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondBar_echoAllPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondBar_echoAllPathKeys( getPath(), getPath() ); // TODO PATH

// First param whole, second unknown

function firstParamWholeSecondUnknown_echoAllPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[rand()] = $second;
	echoAllPathKeys( $sinkArg );
}
firstParamWholeSecondUnknown_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoAllPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondUnknown_echoAllPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondUnknown_echoAllPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondUnknown_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondUnknown_echoAllPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondUnknown_echoAllPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondUnknown_echoAllPathKeys( getPath(), getPath() ); // PATH 1

// First param whole, second key

function firstParamWholeSecondKey_echoAllPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[$second] = 'foo';
	echoAllPathKeys( $sinkArg );
}
firstParamWholeSecondKey_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondKey_echoAllPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondKey_echoAllPathKeys( $_GET['a'], getPath() ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondKey_echoAllPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2, PATH 2
firstParamWholeSecondKey_echoAllPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondKey_echoAllPathKeys( getHTML(), getPath() ); // TODO HTML 1, PATH 2
firstParamWholeSecondKey_echoAllPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1 & 2
firstParamWholeSecondKey_echoAllPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondKey_echoAllPathKeys( getPath(), getPath() ); // TODO PATH 1 & 2

// First and second param foo

function firstParamFooSecondFoo_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first . $second ];
	echoAllPathKeys( $sinkArg );
}
firstParamFooSecondFoo_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondFoo_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondFoo_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondFoo_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondFoo_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamFooSecondFoo_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondFoo_echoAllPathKeys( getPath(), getPath() ); // Safe

// First param foo, second bar

function firstParamFooSecondBar_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, 'bar' => $second ];
	echoAllPathKeys( $sinkArg );
}
firstParamFooSecondBar_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondBar_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondBar_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondBar_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondBar_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondBar_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondBar_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamFooSecondBar_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondBar_echoAllPathKeys( getPath(), getPath() ); // Safe

// First param foo, second unknown

function firstParamFooSecondUnknown_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, rand() => $second ];
	echoAllPathKeys( $sinkArg );
}
firstParamFooSecondUnknown_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondUnknown_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondUnknown_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondUnknown_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondUnknown_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamFooSecondUnknown_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondUnknown_echoAllPathKeys( getPath(), getPath() ); // Safe

// First param foo, second key

function firstParamFooSecondKey_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, $second => 'safe' ];
	echoAllPathKeys( $sinkArg );
}
firstParamFooSecondKey_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamFooSecondKey_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondKey_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamFooSecondKey_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamFooSecondKey_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondKey_echoAllPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondKey_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2, PATH 2
firstParamFooSecondKey_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondKey_echoAllPathKeys( getPath(), getPath() ); // PATH 2

// First and second param bar

function firstParamBarSecondBar_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first . $second ];
	echoAllPathKeys( $sinkArg );
}
firstParamBarSecondBar_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamBarSecondBar_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondBar_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamBarSecondBar_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamBarSecondBar_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamBarSecondBar_echoAllPathKeys( getPath(), getPath() ); // Safe

// First param bar, second unknown

function firstParamBarSecondUnknown_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first, rand() => $second ];
	echoAllPathKeys( $sinkArg );
}
firstParamBarSecondUnknown_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondUnknown_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamBarSecondUnknown_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamBarSecondUnknown_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamBarSecondUnknown_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondUnknown_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamBarSecondUnknown_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamBarSecondUnknown_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamBarSecondUnknown_echoAllPathKeys( getPath(), getPath() ); // Safe

// First param bar, second key

function firstParamBarSecondKey_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first, $second => 'safe' ];
	echoAllPathKeys( $sinkArg );
}
firstParamBarSecondKey_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamBarSecondKey_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamBarSecondKey_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamBarSecondKey_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamBarSecondKey_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamBarSecondKey_echoAllPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamBarSecondKey_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2, PATH 2
firstParamBarSecondKey_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamBarSecondKey_echoAllPathKeys( getPath(), getPath() ); // PATH 2

// First and second param unknown

function firstParamUnknownSecondUnknown_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ rand() => $first, rand() => $second ];
	echoAllPathKeys( $sinkArg );
}
firstParamUnknownSecondUnknown_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamUnknownSecondUnknown_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoAllPathKeys( getHTML(), getPath() ); // HTML 1
firstParamUnknownSecondUnknown_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamUnknownSecondUnknown_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamUnknownSecondUnknown_echoAllPathKeys( getPath(), getPath() ); // Safe

// First param unknown, second key

function firstParamUnknownSecondKey_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ rand() => $first, $second => 'safe' ];
	echoAllPathKeys( $sinkArg );
}
firstParamUnknownSecondKey_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamUnknownSecondKey_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamUnknownSecondKey_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamUnknownSecondKey_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondKey_echoAllPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2, PATH 2
firstParamUnknownSecondKey_echoAllPathKeys( getPath(), getHTML() ); // HTML 2
firstParamUnknownSecondKey_echoAllPathKeys( getPath(), getPath() ); // PATH 2

// First and second param key

function firstParamKeySecondKey_echoAllPathKeys( $first, $second ) {
	$sinkArg = [ $first => 'safe', $second => 'safe' ];
	echoAllPathKeys( $sinkArg );
}
firstParamKeySecondKey_echoAllPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamKeySecondKey_echoAllPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamKeySecondKey_echoAllPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamKeySecondKey_echoAllPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamKeySecondKey_echoAllPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamKeySecondKey_echoAllPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamKeySecondKey_echoAllPathKeys( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamKeySecondKey_echoAllPathKeys( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamKeySecondKey_echoAllPathKeys( getPath(), getPath() ); // PATH 1 & 2
