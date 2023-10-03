<?php

function appendSafeToParam( $x ) {
	$y = $x;
	$y .= 'safe';
	return $y;
}
$taintedArg1 = $_GET['a'];
echo appendSafeToParam( $taintedArg1 ); // Lines 4, 6, 8


function appendUnsafeToParam( $x ) {
	$y = $x;
	$y .= $_GET['x'];
	return $y;
}
$taintedArg2 = $_GET['a'];
echo appendUnsafeToParam( $taintedArg2 ); // Lines 13, 14, 15, 17
$safeArg = 'safe';
echo appendUnsafeToParam( $safeArg ); // Lines 14, 15
$unsafeButNoHTMLArg = htmlspecialchars( $_GET['a'] );
echo appendUnsafeToParam( $unsafeButNoHTMLArg ); // Lines 14, 15

/**
 * @return-taint shell,html
 */
function getShellAndHtml() {
	return $_GET['a'];
}
function preserveOnlyShell( $par ) {
	return htmlspecialchars( $par );
}
$shellAndHtml = getShellAndHtml();
$onlyShell = preserveOnlyShell( $shellAndHtml );
shell_exec( $onlyShell ); // TODO: ShellInjection, caused by lines 34, 31, 33, 27



function escapeFirstPassSecond( $x, $y ) {
	$x = htmlspecialchars( $x );
	return $x . $y;
}
function wrapEscapeFirstPassSecond( $x, $y ) {
	return escapeFirstPassSecond( $x, $y );
}
echo wrapEscapeFirstPassSecond( $_GET['a'], $_GET['b'] ); // TODO: Unsafe, caused by 44 and 41 (and not 40)


function htmlEscapeFirstShellSecond( $x, $y ) {
	$ret = '';
	$ret .= htmlspecialchars( $x );
	$ret .= escapeshellarg( $y );
	return  $ret;
}
echo htmlEscapeFirstShellSecond( $_GET['a'], $_GET['b'] ); // TODO: Unsafe, caused by lines 53 and 52