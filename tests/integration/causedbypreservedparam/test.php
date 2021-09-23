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
