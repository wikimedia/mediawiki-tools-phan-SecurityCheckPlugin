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
	escape( $arg1 ); // TODO Should the SecurityCheck-FalsePositive here have line 8 in its caused-by?
	htmlspecialchars( $arg1 );
}

function test3() {
	noop( $arg1 );
	echo $arg1;// Safe: even if the taint of $arg1 is UNKNOWN before the call, passing by reference creates the var as null, hence safe (known)
}
