<?php

/**
 * @return-taint html
 */
function getHTML() {
	return $_GET['a'];
}
/**
 * @return-taint shell
 */
function getShell() {
	return $_GET['b'];
}


function doubleExec( $x ) {
	echo $x;
	shell_exec( $x );
}
doubleExec( getHTML() ); // XSS, caused by lines 18, 6


function escapePart( $x, $y ) {
	$x = htmlspecialchars( $x );
	$y = escapeshellcmd( $y );
	return $x . $y;
}
function execStuff( $x, $y ) {
	$v = escapePart( $x, $y );
	shell_exec( $v );
}
execStuff( $_GET['a'], $_GET['b'] ); // TODO: ShellInjection, caused by (in this order or an equally sensible one): 31, 30, 27, 25


function testDifferentArgumentTaint() {
	$tainted = getHTML();
	$tainted .= getShell();
	shell_exec( $tainted ); // Caused by line 38, 12
	echo $tainted; // Caused by line 37, 6
}


