<?php

namespace TestClassCrash;

/**
 * @return ClassDoesNotExist|string
 */
function hasNonexistentClassInReturnComment() {
	return $GLOBALS['foo'];
}

/**
 * @param ClassDoesNotExist $x
 */
function hasNonexistentClassInParamComment( $x ) {
	var_dump( $x );
}

( static function () {
	$obj = hasNonexistentClassInReturnComment();
	if ( rand() ) {
		$obj = htmlspecialchars( $obj );
	}
	hasNonexistentClassInParamComment( $obj ); // Avoid: Phan\Exception\CodeBaseException : Cannot find class \ClassDoesNotExist
} )();
