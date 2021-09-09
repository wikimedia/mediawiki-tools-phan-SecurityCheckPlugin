<?php

use Wikimedia\Rdbms\MysqlDatabase;

/**
 * @param string $key
 */
function annotatedAsString( $key ) {
	$db = new MysqlDatabase;
	$conds = [ $key => $_GET['bar'] ];
	'@phan-debug-var-taintedness $conds';
	$db->select( 'table', 'field', $conds ); // We consider this safe, although $key might actually be an int
}

function typehintedAsString( string $key ) {
	$db = new MysqlDatabase;
	$conds = [ $key => $_GET['bar'] ];
	'@phan-debug-var-taintedness $conds';
	$db->select( 'table', 'field', $conds ); // We consider this safe, although the key might be int if $key is the canonical representation of an int
}

/**
 * @return string
 */
function returnsStringDoc() {
	return $GLOBALS['unknown'];
}

function returnsStringReal(): string {
	return $GLOBALS['unknown'];
}

$db = new MysqlDatabase;
$conds1 = [ returnsStringDoc() => $_GET['bar'] ];
'@phan-debug-var-taintedness $conds1';
$db->select( 'table', 'field', $conds1 ); // We consider this safe, although the key might actually be an int
$conds2 = [ returnsStringReal() => $_GET['bar'] ];
'@phan-debug-var-taintedness $conds2';
$db->select( 'table', 'field', $conds2 ); // We consider this safe, although the key could be autocast to int
