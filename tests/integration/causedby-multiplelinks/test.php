<?php

function echoVarWithXAndY( $arg ): void {
	$localVar = $arg['x'];
	$localVar .= $arg['y'];
	echo $localVar;
}

echoVarWithXAndY( [ 'x' => $_GET['a'] ] ); // Unsafe, caused by 6 and 4
echoVarWithXAndY( [ 'y' => $_GET['a'] ] ); // Unsafe, caused by 6 and 5
echoVarWithXAndY( [ 'x' => $_GET['a'], 'y' => 'safe' ] ); // Unsafe, caused by 6 and 4
echoVarWithXAndY( [ 'x' => 'safe', 'y' => $_GET['a'] ] ); // Unsafe, caused by 6 and 5
echoVarWithXAndY( [ 'x' => $_GET['a'], 'y' => $_GET['a'] ] ); // Unsafe, caused by 6, 5 and 4
echoVarWithXAndY( [ 'x' => $_GET['a'], 'unused' => $_GET['b'] ] ); // Unsafe, caused by 6 and 4
echoVarWithXAndY( [ 'y' => $_GET['a'], 'unused' => $_GET['b'] ] ); // Unsafe, caused by 6 and 5

function addYOfSecondArgToFirstAndEchoXOfThat( $first, $second ): void {
	$localVar = $first;
	$localVar += $second['y'];
	echo $localVar['x'];
}

// For the tests below, first argument should only be caused by lines 20 and 18, and second argument by lines 20 and 19
addYOfSecondArgToFirstAndEchoXOfThat( $_GET['a'], $_GET['b'] ); // XSS first and second
addYOfSecondArgToFirstAndEchoXOfThat( $_GET['a'], 'safe' ); // XSS first
addYOfSecondArgToFirstAndEchoXOfThat( 'safe', $_GET['b'] ); // XSS second

addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => 'safe' ], 'safe' ); // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => 'safe' ], $_GET['b'] ); // XSS second
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => 'safe' ], [ 'unused' => $_GET['b'] ] ); // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => 'safe' ], [ 'unused' => 'safe' ] ); // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => 'safe' ], [ 'y' => $_GET['b'] ] ); // XSS second
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => 'safe' ], [ 'y' => 'safe' ] ); // Safe

addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => $_GET['x'] ], 'safe' ); // XSS first
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => $_GET['x'] ], $_GET['b'] ); // XSS first and second
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => $_GET['x'] ], [ 'unused' => $_GET['b'] ] ); // XSS first
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => $_GET['x'] ], [ 'unused' => 'safe' ] );  // XSS first
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => $_GET['x'] ], [ 'y' => $_GET['b'] ] ); // XSS first and second
addYOfSecondArgToFirstAndEchoXOfThat( [ 'x' => $_GET['x'] ], [ 'y' => 'safe' ] ); // XSS first

addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => 'safe' ], 'safe' ); // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => 'safe' ], $_GET['b'] ); // XSS second
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => 'safe' ], [ 'unused' => $_GET['b'] ] ); // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => 'safe' ], [ 'unused' => 'safe' ] ); // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => 'safe' ], [ 'y' => $_GET['b'] ] ); // XSS second
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => 'safe' ], [ 'y' => 'safe' ] ); // Safe

addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => $_GET['x'] ], 'safe' ); // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => $_GET['x'] ], $_GET['b'] ); // XSS second
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => $_GET['x'] ], [ 'unused' => $_GET['b'] ] ); // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => $_GET['x'] ], [ 'unused' => 'safe' ] );  // Safe
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => $_GET['x'] ], [ 'y' => $_GET['b'] ] ); // XSS second
addYOfSecondArgToFirstAndEchoXOfThat( [ 'unused' => $_GET['x'] ], [ 'y' => 'safe' ] ); // Safe