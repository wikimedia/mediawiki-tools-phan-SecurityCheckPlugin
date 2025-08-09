<?php

use Wikimedia\Rdbms\Database;

function unsafeJoinConds( $unsafe ) {
	$db = new Database();
	$db->select( 'x', 'x', '', __METHOD__, [],
		// Backprop unsafe join conds
		[ 't' => [ 'INNER JOIN', [ 1 => $unsafe ] ] ]
	);
}
unsafeJoinConds( $_GET['x'] );

function safeJoinConds( $unsafe ) {
	$db = new Database();
	$db->select( 'x', 'x', '', __METHOD__, [],
		// Do NOT backprop safe join conds
		[ 't' => [ 'INNER JOIN', [ 'safe' => $unsafe ] ] ]
	);
}
safeJoinConds( $_GET['x'] );

function unsafeOptions( $unsafe ) {
	$db = new Database();
	$db->select( 'x', 'x', '', __METHOD__,
		// Backprop unsafe options
		[ 'HAVING' => $unsafe ]
	);
}
unsafeOptions( $_GET['x'] );

function safeOptions( $unsafe ) {
	$db = new Database();
	$db->select( 'x', 'x', '', __METHOD__,
		// Do NOT backprop safe options
		[ 'HAVING' => [ 'safe' => $unsafe ] ]
	);
}
safeOptions( $_GET['x'] );
