<?php

/**
 * @param-taint $t escapes_html Testing text after annotation
 */
function escapeHTML( $t ) {
	return $t;
}

/**
 * @return-taint html Testing text after annotation
 */
function getUnsafeHTML() {
	return '<blink>evil</blink>';
}

/**
 * @return-taint Tainted
 */
function getUserInput() {
	return '!user';
}

/**
 * @param-taint $query exec_SQL
 * @return bool
 */
function doQuery( $query ) {
	return true;
}

/**
 * @param-taint $line exec_shell, array_ok Testing text after annotation
 */
function wfShellExec2( $line ) {
	return 0;
}

/**
 * @return-taint onlysafefor_sql
 */
function getSomeSQL() {
	return 'SELECT 12;';
}

/**
 * @param-taint $foo none
 */
function safeOutput( $foo ) {
	echo $foo;
}

/**
 * @return-taint none
 */
function getSafeString() {
	return $_GET['foo'];
}

/**
 * @return-taint fdasdfa_html
 */
function invalidTaint() {
	return '<foo>';
}

/**
 * @param-taint $t exec_sql,exec_misc,exec_custom1,exec_htmlnoent Testing text after annotation
 */
function multiTaint( $t ) {
	return null;
}

/**
 * @param-taint $foo none
 * @param-taint &$bar exec_html
 */
function passbyRef( $foo, &$bar ) {
	return "f";
}
