<?php

/**
 * @return-taint none
 */
function returnSafe(): string {
	return $_GET['x'];// TODO: This line must NOT appear in caused-by
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
echo passParamThrough( $_GET['a'] ); // TODO: There must be a caused-by line pointing to the annotation
