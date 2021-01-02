<?php

/**
 * @return ClassDoesNotExist|string
 */
function foo() {
	return $GLOBALS['foo'];
}

/**
 * @param ClassDoesNotExist $x
 */
function doStuff( $x ) {
	var_dump( $x );
}

function main() {
	$obj = foo();
	if ( rand() ) {
		$obj = htmlspecialchars( $obj );
	}
	doStuff( $obj ); // Avoid: Phan\Exception\CodeBaseException : Cannot find class \ClassDoesNotExist
}
