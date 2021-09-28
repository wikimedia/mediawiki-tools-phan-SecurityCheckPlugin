<?php

function escapeArg( &$arg1 ) {
	$arg1 = htmlspecialchars( $arg1 );
}
function taintArg( &$arg2 ) { // Note: `taint` is the name of an internal PHP function!
	$arg2 = $_GET['foo'];
}
function taintEchoAndEscape( &$arg3 ) {
	taintArg( $arg3 );
	echo $arg3; // Unsafe caused by lines 10 and 7 (TODO: in this order!), because $arg3 can be seen as a local var
	escapeArg( $arg3 );
}
function maybeTaintEchoAndEscape( &$arg3 ) {
	if ( rand() ) {
		taintArg( $arg3 );
	}
	echo $arg3; // Unsafe caused by lines 16 and 7 (TODO: in this order!), because $arg3 can be seen as a local var
	escapeArg( $arg3 );
}
function escapeEchoAndTaint( &$arg4 ) {
	escapeArg( $arg4 );
	echo $arg4; // Always safe
	taintArg( $arg4 );
}
function maybeEscapeEchoAndTaint( &$arg4 ) {
	if ( rand() ) {
		escapeArg( $arg4 );
	}
	echo $arg4; // This is potentially unsafe, but should only be reported outside the func
	taintArg( $arg4 );
}

function test1() {
	$var1 = '';
	taintEchoAndEscape( $var1 );
	echo $var1; // TODO: Safe
}

function test2() {
	$var2 = $_GET['baz'];
	taintEchoAndEscape( $var2 ); // This isn't reported because the value of $var2 is ignored when echoing (instead, the echo itself is marked as unsafe)
	echo $var2; // TODO: Safe
}

function test3() {
	$var3 = '';
	maybeTaintEchoAndEscape( $var3 );
	echo $var3; // TODO Safe
}

function test4() {
	$var4 = $_GET['baz'];
	maybeTaintEchoAndEscape( $var4 ); // TODO: Unsafe due to the echo
	echo $var4; // TODO: Safe
}

function test5() {
	$var5 = $_GET['unsafe']; // TODO This must not be in caused-by
	escapeEchoAndTaint( $var5 ); // TODO Safe
	echo $var5; // Unsafe, caused by lines 60, 24 and 7 (TODO: in this order!)
}

function test6() {
	$var6 = 'x';
	escapeEchoAndTaint( $var6 );
	echo $var6; // Unsafe, caused by lines 66, 24 and 7 (TODO: in this order!)
}

function test7() {
	$var7 = $_GET['unsafe'];
	maybeEscapeEchoAndTaint( $var7 ); // Unsafe, TODO: caused by lines 71 and 30
	echo $var7; // Unsafe, caused by lines 72, 31 and 7 (TODO: in this order!), and (TODO) NOT line 71
}

function test8() {
	$var8 = 'x';
	maybeEscapeEchoAndTaint( $var8 ); // Safe
	echo $var8; // Unsafe, caused by lines 78, 31 and 7 (TODO: in this order!)
}
