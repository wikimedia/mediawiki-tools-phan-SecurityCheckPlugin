<?php

use Wikimedia\Rdbms\MysqlDatabase;

$db = new MysqlDatabase;

// safe
$db->select(
	'table',
	'field',
	[ 'foo' => $_GET['bar'] ]
);

// safe
$db->select(
	'table',
	'field',
	[ 'foo' => [ $_GET['bar'], $_GET['baz'] ] ]
);

$list = [];
$list[] = $_GET['bar'];
$list[] = $_GET['baz'];
$list[] = $_GET['fred'];

// safe
$db->select( [ 'table', 'table2' ], [ 'field1', 'field2' ], [ 'foo' => $list ], __METHOD__ );
// unsafe
$db->select( [ 'table', 'table2' ], [ 'field1', 'field2' ], $list, __METHOD__ );

$inList = [ 'Foo' => [] ];
$inList['Foo'][] = $_GET['evil1'];
$inList['Foo'][] = $_GET['evil2'];
$inList['bar'] = [ $_GET['evil4'] ];
// safe
$db->select( 'table', '*', $inList, __METHOD__ );

$inner = [];
$inner[] =  $_GET['a'] ;
$key = mysqli_real_escape_string( new mysqli, $GLOBALS['a'] );
$db->select( 'table', '*', [ $key => $inner ], __METHOD__ ); // Safe, NUMKEY is only for the outer array


$foo = 'foo';
$db->select( 'table', '*', [ $foo => $_GET['a'] ], __METHOD__ ); // Safe

$num = 42;
$db->select( 'table', '*', [ $num => $_GET['a'] ], __METHOD__ ); // Unsafe
