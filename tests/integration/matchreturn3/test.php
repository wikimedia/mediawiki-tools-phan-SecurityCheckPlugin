<?php

// Helpers
/**
 * @return-taint html
 */
function getHTML() {
	return 'x';
}

/**
 * @return-taint shell
 */
function getShell() {
	return 'x';
}

/**
 * Helper to get an unknown type but without taint
 * @return-taint none
 */
function getUnknown() {
	return $GLOBALS['unknown'];
}


class TestDontAddOffsetToUnknownInArray {
	static function doTest( $par ) {
		$v = [ 'a' => $par ];
		$y = $v[getUnknown()];
		echo $y;
	}
}
'@taint-check-debug-method-first-arg TestDontAddOffsetToUnknownInArray::doTest';
TestDontAddOffsetToUnknownInArray::doTest( $_GET ); // XSS

class AddOffsetToUnknown {
	static function doTest( $par ) {
		$y = $par[getUnknown()];
		echo $y;
	}
}
'@taint-check-debug-method-first-arg AddOffsetToUnknown::doTest';
AddOffsetToUnknown::doTest( $_GET ); // XSS


function execRoundTrippedPartialOffset( $par ) {
	$a = htmlspecialchars( $par );
	$b = escapeshellcmd( $par['x'] );

	$v = [ 'a' => $a , 'b' => $b];

	$z = $v['a'];
	$k = $z['x']; // Accessing offset of a string here is fine, we only want to ensure that the taintedness isn't lost
	shell_exec( $k ); // Same as shell_exec( htmlspecialchars( $par )['x'] )
}
execRoundTrippedPartialOffset( [ 'x' => $_GET['t'] ] ); // TODO Unsafe

function getRemoveHtmlFromAllShellFromOffset( $par ) {
	$v = htmlspecialchars( $par ) . escapeshellcmd( $par['x'] );
	return $v;
}
function testGetRemoveHtmlFromAllShellFromOffset() {
	$p = getHTML();
	$p['x'] = getShell();
	$v = getRemoveHtmlFromAllShellFromOffset( $p );
	echo $v; // Safe
}

function doesNothing( $par ) {
	$v = htmlspecialchars( $par['x'] ) . escapeshellcmd( $par['x'] );
	return $v;
}
echo doesNothing( $_GET['x'] ); // TODO Unsafe
echo doesNothing( getHTML() ); // TODO Unsafe
echo doesNothing( getShell() ); // Safe


function preserveKeys( $arg ) {
	foreach ( $arg as $k => $v ) {
		return $k;
	}
}
echo preserveKeys( $_GET['a'] ); // TODO Unsafe
echo preserveKeys( getHTML() ); // TODO Unsafe
echo preserveKeys( getShell() ); // Safe
echo preserveKeys( [ 'x' => $_GET['a'] ] ); // Safe
echo preserveKeys( [ 'x' => getHTML() ] ); // Safe
echo preserveKeys( [ 'x' => getShell() ] ); // Safe
echo preserveKeys( [ $_GET['a'] => '' ] ); // TODO Unsafe
echo preserveKeys( [ getHTML() => '' ] ); // TODO Unsafe
echo preserveKeys( [ getShell() => '' ] ); // Safe

function preserveEscapedKeys( $arg ) {
	foreach ( $arg as $k => $v ) {
		return htmlspecialchars( $k );
	}
}
echo preserveEscapedKeys( $_GET['a'] ); // Safe
echo preserveEscapedKeys( getHTML() ); // Safe
echo preserveEscapedKeys( getShell() ); // Safe
echo preserveEscapedKeys( [ 'x' => $_GET['a'] ] ); // Safe
echo preserveEscapedKeys( [ 'x' => getHTML() ] ); // Safe
echo preserveEscapedKeys( [ 'x' => getShell() ] ); // Safe
echo preserveEscapedKeys( [ $_GET['a'] => '' ] ); // Safe
echo preserveEscapedKeys( [ getHTML() => '' ] ); // Safe
echo preserveEscapedKeys( [ getShell() => '' ] ); // Safe
shell_exec( preserveEscapedKeys( $_GET['a'] ) ); // TODO Unsafe
shell_exec( preserveEscapedKeys( getHTML() ) ); // Safe
shell_exec( preserveEscapedKeys( getShell() ) ); // TODO Unsafe
shell_exec( preserveEscapedKeys( [ 'x' => $_GET['a'] ] ) ); // Safe
shell_exec( preserveEscapedKeys( [ 'x' => getHTML() ] ) ); // Safe
shell_exec( preserveEscapedKeys( [ 'x' => getShell() ] ) ); // Safe
shell_exec( preserveEscapedKeys( [ $_GET['a'] => '' ] ) ); // TODO Unsafe
shell_exec( preserveEscapedKeys( [ getHTML() => '' ] ) ); // Safe
shell_exec( preserveEscapedKeys( [ getShell() => '' ] ) ); // TODO Unsafe
