<?php

// First and second param whole

function firstParamWholeSecondWhole_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = $first . $second;
	echoFooKeyPathKeys( $sinkArg );
}
firstParamWholeSecondWhole_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondWhole_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondWhole_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondWhole_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondWhole_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondWhole_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondWhole_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondWhole_echoFooKeyPathKeys( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondWhole_echoFooKeyPathKeys( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second foo

function firstParamWholeSecondFoo_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['foo'] = $second;
	echoFooKeyPathKeys( $sinkArg );
}
firstParamWholeSecondFoo_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondFoo_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondFoo_echoFooKeyPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondFoo_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondFoo_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondFoo_echoFooKeyPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondFoo_echoFooKeyPathKeys( getPath(), getPath() ); // PATH 1

// First param whole, second bar

function firstParamWholeSecondBar_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['bar'] = $second;
	echoFooKeyPathKeys( $sinkArg );
}
firstParamWholeSecondBar_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 1
firstParamWholeSecondBar_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1, PATH 1
firstParamWholeSecondBar_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondBar_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 1
firstParamWholeSecondBar_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamWholeSecondBar_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondBar_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // PATH 1
firstParamWholeSecondBar_echoFooKeyPathKeys( getPath(), getHTML() ); // PATH 1
firstParamWholeSecondBar_echoFooKeyPathKeys( getPath(), getPath() ); // PATH 1

// First param whole, second unknown

function firstParamWholeSecondUnknown_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[rand()] = $second;
	echoFooKeyPathKeys( $sinkArg );
}
firstParamWholeSecondUnknown_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondUnknown_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2
firstParamWholeSecondUnknown_echoFooKeyPathKeys( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondUnknown_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondUnknown_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1
firstParamWholeSecondUnknown_echoFooKeyPathKeys( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondUnknown_echoFooKeyPathKeys( getPath(), getPath() ); // PATH 1

// First param whole, second key

function firstParamWholeSecondKey_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[$second] = 'foo';
	echoFooKeyPathKeys( $sinkArg );
}
firstParamWholeSecondKey_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondKey_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1, PATH 1
firstParamWholeSecondKey_echoFooKeyPathKeys( $_GET['a'], getPath() ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondKey_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // TODO HTML 1, PATH 2
firstParamWholeSecondKey_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamWholeSecondKey_echoFooKeyPathKeys( getHTML(), getPath() ); // TODO HTML 1, PATH 2
firstParamWholeSecondKey_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // TODO PATH 1 & 2
firstParamWholeSecondKey_echoFooKeyPathKeys( getPath(), getHTML() ); // PATH 1
firstParamWholeSecondKey_echoFooKeyPathKeys( getPath(), getPath() ); // TODO PATH 1 & 2

// First and second param foo

function firstParamFooSecondFoo_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first . $second ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamFooSecondFoo_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondFoo_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondFoo_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondFoo_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondFoo_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamFooSecondFoo_echoFooKeyPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondFoo_echoFooKeyPathKeys( getPath(), getPath() ); // Safe

// First param foo, second bar

function firstParamFooSecondBar_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, 'bar' => $second ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamFooSecondBar_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1
firstParamFooSecondBar_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1
firstParamFooSecondBar_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondBar_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 1
firstParamFooSecondBar_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamFooSecondBar_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondBar_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // Safe
firstParamFooSecondBar_echoFooKeyPathKeys( getPath(), getHTML() ); // Safe
firstParamFooSecondBar_echoFooKeyPathKeys( getPath(), getPath() ); // Safe

// First param foo, second unknown

function firstParamFooSecondUnknown_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, rand() => $second ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamFooSecondUnknown_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondUnknown_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamFooSecondUnknown_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamFooSecondUnknown_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1
firstParamFooSecondUnknown_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamFooSecondUnknown_echoFooKeyPathKeys( getPath(), getHTML() ); // HTML 2
firstParamFooSecondUnknown_echoFooKeyPathKeys( getPath(), getPath() ); // Safe

// First param foo, second key

function firstParamFooSecondKey_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ 'foo' => $first, $second => 'safe' ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamFooSecondKey_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 2
firstParamFooSecondKey_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1
firstParamFooSecondKey_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamFooSecondKey_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 1, PATH 2
firstParamFooSecondKey_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamFooSecondKey_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondKey_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // PATH 2
firstParamFooSecondKey_echoFooKeyPathKeys( getPath(), getHTML() ); // Safe
firstParamFooSecondKey_echoFooKeyPathKeys( getPath(), getPath() ); // PATH 2

// First and second param bar

function firstParamBarSecondBar_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first . $second ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamBarSecondBar_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // Safe
firstParamBarSecondBar_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // Safe
firstParamBarSecondBar_echoFooKeyPathKeys( $_GET['a'], getPath() ); // Safe
firstParamBarSecondBar_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // Safe
firstParamBarSecondBar_echoFooKeyPathKeys( getHTML(), getHTML() ); // Safe
firstParamBarSecondBar_echoFooKeyPathKeys( getHTML(), getPath() ); // Safe
firstParamBarSecondBar_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // Safe
firstParamBarSecondBar_echoFooKeyPathKeys( getPath(), getHTML() ); // Safe
firstParamBarSecondBar_echoFooKeyPathKeys( getPath(), getPath() ); // Safe

// First param bar, second unknown

function firstParamBarSecondUnknown_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first, rand() => $second ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamBarSecondUnknown_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 2
firstParamBarSecondUnknown_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 2
firstParamBarSecondUnknown_echoFooKeyPathKeys( $_GET['a'], getPath() ); // Safe
firstParamBarSecondUnknown_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 2
firstParamBarSecondUnknown_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 2
firstParamBarSecondUnknown_echoFooKeyPathKeys( getHTML(), getPath() ); // Safe
firstParamBarSecondUnknown_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamBarSecondUnknown_echoFooKeyPathKeys( getPath(), getHTML() ); // HTML 2
firstParamBarSecondUnknown_echoFooKeyPathKeys( getPath(), getPath() ); // Safe

// First param bar, second key

function firstParamBarSecondKey_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ 'bar' => $first, $second => 'safe' ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamBarSecondKey_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // PATH 2
firstParamBarSecondKey_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // Safe
firstParamBarSecondKey_echoFooKeyPathKeys( $_GET['a'], getPath() ); // PATH 2
firstParamBarSecondKey_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // PATH 2
firstParamBarSecondKey_echoFooKeyPathKeys( getHTML(), getHTML() ); // Safe
firstParamBarSecondKey_echoFooKeyPathKeys( getHTML(), getPath() ); // PATH 2
firstParamBarSecondKey_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // PATH 2
firstParamBarSecondKey_echoFooKeyPathKeys( getPath(), getHTML() ); // Safe
firstParamBarSecondKey_echoFooKeyPathKeys( getPath(), getPath() ); // PATH 2

// First and second param unknown

function firstParamUnknownSecondUnknown_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ rand() => $first, rand() => $second ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // HTML 2
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( getPath(), getHTML() ); // HTML 2
firstParamUnknownSecondUnknown_echoFooKeyPathKeys( getPath(), getPath() ); // Safe

// First param unknown, second key

function firstParamUnknownSecondKey_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ rand() => $first, $second => 'safe' ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamUnknownSecondKey_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // HTML 1
firstParamUnknownSecondKey_echoFooKeyPathKeys( $_GET['a'], getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoFooKeyPathKeys( getHTML(), getHTML() ); // HTML 1
firstParamUnknownSecondKey_echoFooKeyPathKeys( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondKey_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // PATH 2
firstParamUnknownSecondKey_echoFooKeyPathKeys( getPath(), getHTML() ); // Safe
firstParamUnknownSecondKey_echoFooKeyPathKeys( getPath(), getPath() ); // PATH 2

// First and second param key

function firstParamKeySecondKey_echoFooKeyPathKeys( $first, $second ) {
	$sinkArg = [ $first => 'safe', $second => 'safe' ];
	echoFooKeyPathKeys( $sinkArg );
}
firstParamKeySecondKey_echoFooKeyPathKeys( $_GET['a'], $_GET['b'] ); // PATH 1 & 2
firstParamKeySecondKey_echoFooKeyPathKeys( $_GET['a'], getHTML() ); // PATH 1
firstParamKeySecondKey_echoFooKeyPathKeys( $_GET['a'], getPath() ); // PATH 1 & 2
firstParamKeySecondKey_echoFooKeyPathKeys( getHTML(), $_GET['b'] ); // PATH 2
firstParamKeySecondKey_echoFooKeyPathKeys( getHTML(), getHTML() ); // Safe
firstParamKeySecondKey_echoFooKeyPathKeys( getHTML(), getPath() ); // PATH 2
firstParamKeySecondKey_echoFooKeyPathKeys( getPath(), $_GET['b'] ); // PATH 1 & 2
firstParamKeySecondKey_echoFooKeyPathKeys( getPath(), getHTML() ); // PATH 1
firstParamKeySecondKey_echoFooKeyPathKeys( getPath(), getPath() ); // PATH 1 & 2
