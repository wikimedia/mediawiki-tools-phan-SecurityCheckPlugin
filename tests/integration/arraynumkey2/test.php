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
