<?php

require "db.php";

use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\MysqlDatabase;

$dbr = new MysqlDatabase;

$colName = 'rev_' . $_GET['colName'];
$dbr->query( $dbr->makeList( [ $colName => 'Foo' ], LIST_AND ), '' ); // unsafe

// Everything safe except where marked otherwise
// COMMA

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ] ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ] ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ] ), '' );
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ] ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], LIST_COMMA ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], LIST_COMMA ), '' );
$dbr->query(
	$dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], LIST_COMMA ),
	''
);
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], LIST_COMMA ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], IDatabase::LIST_COMMA ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], IDatabase::LIST_COMMA ), '' );
$dbr->query(
	$dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], IDatabase::LIST_COMMA ),
	''
);
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], IDatabase::LIST_COMMA ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], Database::LIST_COMMA ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], Database::LIST_COMMA ), '' );
$dbr->query(
	$dbr->makeList(
		[ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ],
		Database::LIST_COMMA
	),
	''
);
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], Database::LIST_COMMA ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], 0 ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], 0 ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], 0 ), '' );

// AND

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], LIST_AND ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], LIST_AND ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], LIST_AND ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], LIST_AND ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], IDatabase::LIST_AND ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], IDatabase::LIST_AND ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], IDatabase::LIST_AND ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], IDatabase::LIST_AND ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], Database::LIST_AND ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], Database::LIST_AND ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], Database::LIST_AND ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], Database::LIST_AND ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], 1 ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], 1 ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], 1 ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], 1 ), '' );

// Check assumption that unknown = LIST_AND.

$type = $GLOBALS['foo'];
$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], $type ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], $type ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], $type ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], $type ), '' );

// OR

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], LIST_OR ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], LIST_OR ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], LIST_OR ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], LIST_OR ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], IDatabase::LIST_OR ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], IDatabase::LIST_OR ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], IDatabase::LIST_OR ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], IDatabase::LIST_OR ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], Database::LIST_OR ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], Database::LIST_OR ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], Database::LIST_OR ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], Database::LIST_OR ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], 4 ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], 4 ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], 4 ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], 4 ), '' );

// SET

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], LIST_SET ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], LIST_SET ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], LIST_SET ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], LIST_SET ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], IDatabase::LIST_SET ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], IDatabase::LIST_SET ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], IDatabase::LIST_SET ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], IDatabase::LIST_SET ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], Database::LIST_SET ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], Database::LIST_SET ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], Database::LIST_SET ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], Database::LIST_SET ), '' );

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], 2 ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], 2 ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], 2 ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], 2 ), '' );

// NAMES

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], LIST_NAMES ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], LIST_NAMES ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], LIST_NAMES ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], LIST_NAMES ), '' ); // unsafe

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], IDatabase::LIST_NAMES ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], IDatabase::LIST_NAMES ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], IDatabase::LIST_NAMES ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], IDatabase::LIST_NAMES ), '' ); // unsafe

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], Database::LIST_NAMES ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], Database::LIST_NAMES ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], Database::LIST_NAMES ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], Database::LIST_NAMES ), '' ); // unsafe

$dbr->query( $dbr->makeList( [ 'fafad', 'adfafd' ], 3 ), '' );
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], 3 ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] => $_GET['evil3'] ], 3 ), '' ); // unsafe
$dbr->query( $dbr->makeList( [ 'foo',  'bar' => $_GET['evil3'] ], 3 ), '' ); // unsafe

// NON-LITERAL
$type1= LIST_COMMA;
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], $type1 ), '' ); // Safe
$type2= IDatabase::LIST_COMMA;
$dbr->query( $dbr->makeList( [ $_GET['evil'],  $_GET['evil2'] ], $type2 ), '' ); // Safe
$type3 = IDatabase::LIST_AND;
$dbr->query( $dbr->makeList( [ $_GET['evil'], $_GET['evil2'] ], $type3 ), '' ); // Unsafe
function getType4() {
	return LIST_AND;
}
$dbr->query( $dbr->makeList( [ $_GET['evil'], $_GET['evil2'] ], getType4() ), '' ); // Unsafe
