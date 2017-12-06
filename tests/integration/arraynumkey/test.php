<?php

use Wikimedia\Rdbms\MysqlDatabase;

$db = new MysqlDatabase;

// safe
$db->select(
	'table',
	'field',
	[ 'foo' => $_GET['bar'] ]
);

// unsafe
$db->select(
	'table',
	'field',
	[ $_GET['bar'] ]
);

// unsafe
$db->select(
	'table',
	'field',
	$_GET['bar']
);

$where = [];
$where[] = $_GET['bar'];

// unsafe
$db->select(
	'table',
	'field',
	$where
);

$where2 = [ $_GET['bar'] ];
$where2[] = 'Something';
// unsafe
$db->select( 't', 'f', $where2 );

// unsafe
$where3 = [ $_GET['d'] => 'foo' ];
$db->select( 't', 'f', $where3 );

$where4 = [];
$where4[$_GET['d']] = 'foo';
// unsafe
$db->select( 't', 'f', $where4 );

// unsafe
$where5 = [ 1 => $_GET['d'] ];
$db->select( 't', 'f', $where5 );

// unsafe
$db->select( 't', 'f', '', __METHOD__, [],
	[
		't' => [ 'INNER JOIN', $where5 ]
	]
);

// unsafe
$db->select( 't', 'f', '', __METHOD__, [],
	[
		't' => [ 'INNER JOIN', $_GET['string'] ]
	]
);

// unsafe
$db->select( 't', 'f', '', __METHOD__, [],
	[
		'HAVING' => $where5
	]
);

// unsafe
$db->select( 't', 'f', '', __METHOD__,
	[
		'HAVING' => $_GET['string']
	]
);

$b = (int)$_GET['b'];
$db->select( 't', 'f', [ 'foo' => $_GET['a'], "bar > $b" ] );

$row = (object)[ 'foo' => $_GET['bar'] ];
$db->selectRow( 't', 'f', [ 'foo2' => $row->foo ] );
$whereRow = [ 'foo2' => $row->foo ];
$db->selectRow( 't', 'f', $whereRow );

$subquery = $db->selectSQLText(
	'Foo',
	'1',
	[ 'value' => $_GET['val'], 'baz=red', 'foo' => '<script>' ],
	__METHOD__,
	[ 'LIMIT' => 1 ]
);

// safe
$db->select(
	'Bar',
	'*',
	[ 'NOT EXISTS( ' . $subquery . ')' ],
	__METHOD__
);

// unsafe
echo $subquery;
