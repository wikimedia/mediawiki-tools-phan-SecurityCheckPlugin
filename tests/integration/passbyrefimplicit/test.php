<?php
// Tests for when a variable is passed by ref without being explicitly declared first
function unsafe( &$x ) {
	$x = $_GET['a'];
}

function escape( &$x ) {
	$x = htmlspecialchars( $x );
}

function noop( &$x ) {
}

function test1() {
	unsafe( $arg1 );
	echo $arg1;
}

function test2() {
	escape( $arg1 );
	htmlspecialchars( $arg1 );
}

function test3() {
	noop( $arg1 );
	echo $arg1;// This is a LikelyFalsePositive right now, see comment in handlePassByRef
}
