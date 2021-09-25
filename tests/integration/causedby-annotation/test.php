<?php

/**
 * @return-taint none
 */
function returnSafe(): string {
	return $_GET['x'];// This line must NOT appear in caused-by
}

function testCausedBy() {
	$x = $_GET['unsafe'];
	$x .= returnSafe();
	echo $x;
}

/**
 * @param-taint $par tainted
 */
function passParamThrough( $par ) {
}
echo passParamThrough( $_GET['a'] ); // There must be a caused-by line pointing to the annotation


/**
 * @param-taint $par exec_html
 */
function execArg( $par ) {
	echo $par;
}
execArg( $_GET['a'] ); // Must have the annotation in caused-by, and NOT line 28

/**
 * @param-taint $par html
 */
function preserveHtmlAnnotatedAddYes( $par ) {
	$ret = $par;
	$ret .= $_GET['a'];
	return $ret;
}
echo preserveHtmlAnnotatedAddYes( $_GET['a'] ); // Must have lines 38, 37 and annotation (not 36)
shell_exec( preserveHtmlAnnotatedAddYes( $_GET['a'] ) ); // Must have lines 38, 37 (not 36 and annotation)
