<?php

/**
 * @return-taint sql_numkey
 */
function getPureNumkey() {
	return 'placeholder';
}

/** @param-taint $arg exec_sql_numkey */
function execNumkey( $arg ) {
}

/**
 * @param-taint $arg exec_sql_numkey,exec_html
 */
function execNumkeyAndHTML( $arg ) {
	return 'placeholder';
}

/**
 * Helper to get an unknown type but without taint
 * @return-taint none
 */
function unknownType() {
	return $GLOBALS['unknown'];
}
