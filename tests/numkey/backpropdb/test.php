<?php

use Wikimedia\Rdbms\Database;

function tableNameBackprop( $unsafe ) {
	$db = new Database();
	// First param is always backpropagated, this is a sanity check
	$db->select( $unsafe, 'x', '' );
}
tableNameBackprop( $_GET['x'] );

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

// Database::where is also tested in backpropnumkey
function unsafeWhere( $unsafe ) {
	$db = new Database();
	$db->select( 'x', 'x', [ $unsafe ] );
}
unsafeWhere( $_GET['x'] );

function safeWhere( $unsafe ) {
	$db = new Database();
	$db->select( 'x', 'x', [ 'safe' => $unsafe ] );
}
safeWhere( $_GET['x'] );
