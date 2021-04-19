<?php

use Wikimedia\Rdbms\MysqlDatabase;

$db = new MysqlDatabase;

$rows = [// TODO Ideally this shouldn't be in caused-by, but normalizeTaintednessForCausedBy adds SQL for completeness
	'first' => 1,
	'second' => 2,
	'fifth' => $_GET['fifth']
];

$rows2 = [
	'third' => 'something'
];

$unsafe = [
	"fourth = fourth+" . $_GET['increment']
];

$db->insert(
	'foo',
	$rows + $rows2,
	__METHOD__
);

$db->insert(
	'foo',
	$unsafe,
	__METHOD__
);

$db->insert(
	'foo',
	$rows + $rows2 + $unsafe,
	__METHOD__
);

$db->insert(
	'foo',
	[
		[ 'first' => $_GET['a'] ],
		[ 'first' => $_GET['a'] ],
		[ 'first' => $_GET['a'] ],
	],
	__METHOD__
);

$items = [];
$items[] = [ 'first' => $_GET['a'] ];
$items[] = [ 'first' => $_GET['a'] ];
$items[] = [ 'first' => $_GET['a'] ];

$db->insert( 'foo', $items, __METHOD__ );

$insertBatch = [];
$insertRow = [ 'first' => $_GET['evil'] ];
$insertBatch[] = $insertRow;
$insertBatch[] = [ 'first' => $_GET['evil2'] ];
$db->insert( 'foo', $insertBatch, __METHOD__, [ 'IGNORE' ] );
