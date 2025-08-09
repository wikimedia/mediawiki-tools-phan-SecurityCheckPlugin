<?php

// safe (numkey inside non-numkey)
execNumkey( [ 'foo' => [ $_GET['bar'], $_GET['baz'] ] ] );

$listInStringKey = [ 'Foo' => [] ];
$listInStringKey['Foo'][] = $_GET['evil1'];
$listInStringKey['Foo'][] = $_GET['evil2'];
$listInStringKey['bar'] = [ $_GET['evil4'] ];
execNumkey( $listInStringKey ); // Safe

$innerNumkey = [];
$innerNumkey[] =  $_GET['a'] ;
$escapedKey = mysqli_real_escape_string( new mysqli, $GLOBALS['a'] );
execNumkey( [ $escapedKey => $innerNumkey ] ); // Safe, NUMKEY is only for the outer array


$stringVar = 'foo';
execNumkey( [ $stringVar => $_GET['a'] ] ); // Safe

$numVar = 42;
execNumkey( [ $numVar => $_GET['a'] ] ); // Unsafe


// Test array_merge
$ref = [ 'string' => [ $_GET['x'] ] ];
'@phan-debug-var-taintedness $ref';
$merged = array_merge( [], [ 'string' => [ $_GET['x'] ] ] );
'@phan-debug-var-taintedness $merged'; // Should be the same as $ref
execNumkey( $ref ); // Safe
execNumkey( $merged ); // Safe

function testNumkeyArrayMergeBranching() {
	if ( rand() ) {
		$safe = [ 'foo' => 42 ];
	} else {
		$safe = [ 'bar' => 100 ];
	}

	$search = [];
	foreach ( $_GET as $val ) {
		$search[] = $val;
	}
	if ( $search ) {
		$conds = array_merge(
			$safe,
			[ 'something' => $search ]
		);
		execNumkey( $conds ); // Safe
	}
}
