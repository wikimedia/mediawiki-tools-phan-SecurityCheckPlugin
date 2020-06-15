<?php

function paramReassign( $par ) {
	$var = $par;
	if ( rand() ) {
		$var = htmlspecialchars( $_GET['foo'] );
	} else {
		f2( $var ); // This is not double escaped
	}
}

function branchOnly() {
	$var = $_GET['baz'];
	if ( rand() ) {
		$var = htmlspecialchars( $_GET['foo'] );
	} else {
		f2( $var ); // This is not double escaped
	}
}

function f2( $par ) {
	$res = htmlspecialchars( $_GET['foo'] );
	$res = rand()
		? htmlspecialchars( $par )
		: $res;

	return $res;
}

function direct() {
	$var = $_GET['baz'];
	if ( rand() ) {
		$var = htmlspecialchars( $_GET['foo'] );
	} else {
		htmlspecialchars( $var ); // Not double escaped
	}
}

function xss() {
	$var = 'foo';
	if ( rand() ) {
		$var = $_GET['baz'];
	} else {
		echo $var; // Safe
	}
}

$a = $_GET['evil'];
if ( foo() ) {
	$a = htmlspecialchars( $a );
} else {
	$a = htmlspecialchars( $a );
}
echo $a; // Safe!
