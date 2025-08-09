<?php

use Wikimedia\Rdbms\MysqlDatabase;

$db = new MysqlDatabase;

$arrayWithNumKey = [ 1 => $_GET['a'] ];

// unsafe
$db->select( 't', 'f', '', __METHOD__, [],
	[
		't' => [ 'INNER JOIN', $arrayWithNumKey ]
	]
);

// unsafe
$db->select( 't', 'f', '', __METHOD__, [],
	[
		't' => [ 'INNER JOIN', $_GET['string'] ]
	]
);

// unsafe
$db->select( 't', 'f', '', __METHOD__,
	[
		'HAVING' => $arrayWithNumKey
	]
);

// unsafe
$db->select( 't', 'f', '', __METHOD__,
	[
		'HAVING' => $_GET['string']
	]
);

// Test literal join conditions (should not crash)
$db->select( 't', 'f', '', __METHOD__, [], [ 't' => [ 'INNER JOIN', 'a=b' ] ] );

// Test literal options (should not crash)
$db->select( 't', 'f', '', __METHOD__, [ 'ORDER BY' => 'foo' ] );
