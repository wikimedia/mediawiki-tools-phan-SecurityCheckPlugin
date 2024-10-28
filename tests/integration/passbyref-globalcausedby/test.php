<?php

// Test to verify that caused-by lines are never overwritten for global variables.

function maybeTaintArg( &$arg ) {
	if ( rand() ) {
		$arg = $_GET['a'];
	}
}

function testInFunctionScope() {
	global $globalInLocalScope;
	$globalInLocalScope = $_GET['a'];
	maybeTaintArg( $globalInLocalScope );
	echo $globalInLocalScope; // XSS caused by 13, 14, 7
}

// Test in global scope
$globalInGlobalScope = $_GET['a'];
maybeTaintArg( $globalInGlobalScope );
echo $globalInGlobalScope; // XSS caused by 19, 20, 7
