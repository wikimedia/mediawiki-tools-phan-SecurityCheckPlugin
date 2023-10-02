<?php

// First and second param whole

function firstParamWholeSecondWhole_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = $first . $second;
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamWholeSecondWhole_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondWhole_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamWholeSecondWhole_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamWholeSecondWhole_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamWholeSecondWhole_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamWholeSecondWhole_echoFooKeyPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamWholeSecondWhole_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamWholeSecondWhole_echoFooKeyPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamWholeSecondWhole_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param whole, second foo

function firstParamWholeSecondFoo_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['foo'] = $second;
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamWholeSecondFoo_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondFoo_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondFoo_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondFoo_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2, PATH 2
firstParamWholeSecondFoo_echoFooKeyPathUnknown( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondFoo_echoFooKeyPathUnknown( getHTML(), getPath() ); // TODO HTML 1, PATH 2
firstParamWholeSecondFoo_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1 & 2
firstParamWholeSecondFoo_echoFooKeyPathUnknown( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondFoo_echoFooKeyPathUnknown( getPath(), getPath() ); // TODO PATH 1 & 2

// First param whole, second bar

function firstParamWholeSecondBar_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = $first;
	$sinkArg['bar'] = $second;
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamWholeSecondBar_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondBar_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1, PATH 1
firstParamWholeSecondBar_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondBar_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // TODO HTML 1, PATH 2
firstParamWholeSecondBar_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1
firstParamWholeSecondBar_echoFooKeyPathUnknown( getHTML(), getPath() ); // TODO HTML 1, PATH 2
firstParamWholeSecondBar_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // TODO PATH 1 & 2
firstParamWholeSecondBar_echoFooKeyPathUnknown( getPath(), getHTML() ); // PATH 1
firstParamWholeSecondBar_echoFooKeyPathUnknown( getPath(), getPath() ); // TODO PATH 1 & 2

// First param whole, second unknown

function firstParamWholeSecondUnknown_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[rand()] = $second;
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // TODO HTML 1 & 2, PATH 1 & 2
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // TODO HTML 1 & 2, PATH 1
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // TODO HTML 1, PATH 1 & 2
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // TODO HTML 1 & 2, PATH 2
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( getHTML(), getHTML() ); // TODO HTML 1 & 2
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( getHTML(), getPath() ); // TODO HTML 1, PATH 2
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // TODO HTML 2, PATH 1 & 2
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( getPath(), getHTML() ); // TODO HTML 2, PATH 1
firstParamWholeSecondUnknown_echoFooKeyPathUnknown( getPath(), getPath() ); // TODO PATH 1 & 2

// First param whole, second key

function firstParamWholeSecondKey_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = $first;
	$sinkArg[$second] = 'foo';
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamWholeSecondKey_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 1
firstParamWholeSecondKey_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1, PATH 1
firstParamWholeSecondKey_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamWholeSecondKey_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // HTML 1
firstParamWholeSecondKey_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1
firstParamWholeSecondKey_echoFooKeyPathUnknown( getHTML(), getPath() ); // HTML 1
firstParamWholeSecondKey_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // PATH 1
firstParamWholeSecondKey_echoFooKeyPathUnknown( getPath(), getHTML() ); // PATH 1
firstParamWholeSecondKey_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1

// First and second param foo

function firstParamFooSecondFoo_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ 'foo' => $first . $second ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamFooSecondFoo_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamFooSecondFoo_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondFoo_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamFooSecondFoo_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamFooSecondFoo_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondFoo_echoFooKeyPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondFoo_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamFooSecondFoo_echoFooKeyPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondFoo_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param foo, second bar

function firstParamFooSecondBar_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ 'foo' => $first, 'bar' => $second ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamFooSecondBar_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 1 & 2
firstParamFooSecondBar_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1, PATH 1
firstParamFooSecondBar_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamFooSecondBar_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // HTML 1, PATH 2
firstParamFooSecondBar_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1
firstParamFooSecondBar_echoFooKeyPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondBar_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // PATH 1 & 2
firstParamFooSecondBar_echoFooKeyPathUnknown( getPath(), getHTML() ); // PATH 1
firstParamFooSecondBar_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param foo, second unknown

function firstParamFooSecondUnknown_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ 'foo' => $first, rand() => $second ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamFooSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamFooSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamFooSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamFooSecondUnknown_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamFooSecondUnknown_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamFooSecondUnknown_echoFooKeyPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamFooSecondUnknown_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamFooSecondUnknown_echoFooKeyPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamFooSecondUnknown_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param foo, second key

function firstParamFooSecondKey_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ 'foo' => $first, $second => 'safe' ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamFooSecondKey_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 1
firstParamFooSecondKey_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1, PATH 1
firstParamFooSecondKey_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamFooSecondKey_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // HTML 1
firstParamFooSecondKey_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1
firstParamFooSecondKey_echoFooKeyPathUnknown( getHTML(), getPath() ); // HTML 1
firstParamFooSecondKey_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // PATH 1
firstParamFooSecondKey_echoFooKeyPathUnknown( getPath(), getHTML() ); // PATH 1
firstParamFooSecondKey_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1

// First and second param bar

function firstParamBarSecondBar_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ 'bar' => $first . $second ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamBarSecondBar_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // PATH 1 & 2
firstParamBarSecondBar_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // PATH 1
firstParamBarSecondBar_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // PATH 1 & 2
firstParamBarSecondBar_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // PATH 2
firstParamBarSecondBar_echoFooKeyPathUnknown( getHTML(), getHTML() ); // Safe
firstParamBarSecondBar_echoFooKeyPathUnknown( getHTML(), getPath() ); // PATH 2
firstParamBarSecondBar_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // PATH 1 & 2
firstParamBarSecondBar_echoFooKeyPathUnknown( getPath(), getHTML() ); // PATH 1
firstParamBarSecondBar_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param bar, second unknown

function firstParamBarSecondUnknown_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ 'bar' => $first, rand() => $second ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamBarSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // TODO HTML 2, PATH 1 & 2
firstParamBarSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // TODO HTML 2, PATH 1
firstParamBarSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // TODO PATH 1 & 2
firstParamBarSecondUnknown_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // TODO HTML 2, PATH 2
firstParamBarSecondUnknown_echoFooKeyPathUnknown( getHTML(), getHTML() ); // TODO HTML 2
firstParamBarSecondUnknown_echoFooKeyPathUnknown( getHTML(), getPath() ); // TODO PATH 2
firstParamBarSecondUnknown_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamBarSecondUnknown_echoFooKeyPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamBarSecondUnknown_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param bar, second key

function firstParamBarSecondKey_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ 'bar' => $first, $second => 'safe' ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamBarSecondKey_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // PATH 1
firstParamBarSecondKey_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // PATH 1
firstParamBarSecondKey_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // PATH 1
firstParamBarSecondKey_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // Safe
firstParamBarSecondKey_echoFooKeyPathUnknown( getHTML(), getHTML() ); // Safe
firstParamBarSecondKey_echoFooKeyPathUnknown( getHTML(), getPath() ); // Safe
firstParamBarSecondKey_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // PATH 1
firstParamBarSecondKey_echoFooKeyPathUnknown( getPath(), getHTML() ); // PATH 1
firstParamBarSecondKey_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1

// First and second param unknown

function firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ rand() => $first, rand() => $second ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1 & 2, PATH 1 & 2
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1 & 2, PATH 1
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1 & 2
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // HTML 1 & 2, PATH 2
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1 & 2
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( getHTML(), getPath() ); // HTML 1, PATH 2
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // HTML 2, PATH 1 & 2
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( getPath(), getHTML() ); // HTML 2, PATH 1
firstParamUnknownSecondUnknown_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1 & 2

// First param unknown, second key

function firstParamUnknownSecondKey_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ rand() => $first, $second => 'safe' ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamUnknownSecondKey_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // HTML 1, PATH 1
firstParamUnknownSecondKey_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // HTML 1, PATH 1
firstParamUnknownSecondKey_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // HTML 1, PATH 1
firstParamUnknownSecondKey_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // HTML 1
firstParamUnknownSecondKey_echoFooKeyPathUnknown( getHTML(), getHTML() ); // HTML 1
firstParamUnknownSecondKey_echoFooKeyPathUnknown( getHTML(), getPath() ); // HTML 1
firstParamUnknownSecondKey_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // PATH 1
firstParamUnknownSecondKey_echoFooKeyPathUnknown( getPath(), getHTML() ); // PATH 1
firstParamUnknownSecondKey_echoFooKeyPathUnknown( getPath(), getPath() ); // PATH 1

// First and second param key

function firstParamKeySecondKey_echoFooKeyPathUnknown( $first, $second ) {
	$sinkArg = [ $first => 'safe', $second => 'safe' ];
	echoFooKeyPathUnknown( $sinkArg );
}
firstParamKeySecondKey_echoFooKeyPathUnknown( $_GET['a'], $_GET['b'] ); // Safe
firstParamKeySecondKey_echoFooKeyPathUnknown( $_GET['a'], getHTML() ); // Safe
firstParamKeySecondKey_echoFooKeyPathUnknown( $_GET['a'], getPath() ); // Safe
firstParamKeySecondKey_echoFooKeyPathUnknown( getHTML(), $_GET['b'] ); // Safe
firstParamKeySecondKey_echoFooKeyPathUnknown( getHTML(), getHTML() ); // Safe
firstParamKeySecondKey_echoFooKeyPathUnknown( getHTML(), getPath() ); // Safe
firstParamKeySecondKey_echoFooKeyPathUnknown( getPath(), $_GET['b'] ); // Safe
firstParamKeySecondKey_echoFooKeyPathUnknown( getPath(), getHTML() ); // Safe
firstParamKeySecondKey_echoFooKeyPathUnknown( getPath(), getPath() ); // Safe
