<?php

// Test for pass-by-refs where an argument other than the first one is passed by-reference, and the first argument's
// value is assigned to it.

function assignFirstToSecond( $arg1, &$arg2 ) {
	$arg2 = $arg1;
}

function doTest() {
	$output = '';
	assignFirstToSecond( $_GET['user'], $output );
	echo $output; // XSS caused by 12, 7
}
