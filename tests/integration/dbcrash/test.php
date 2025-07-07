<?php

use Wikimedia\Rdbms\IDatabase;

function getDB(): IDatabase {
	return $GLOBALS['db'];
}
$db = getDB();

// These would crash due to the array unpacking
$db->select(
	't',
	'*',
	[],
	'',
	[
		...$options,
	],
	[
		...$joinConds
	]
);