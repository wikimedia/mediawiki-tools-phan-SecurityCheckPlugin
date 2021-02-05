<?php

// This test ensures that method links are correctly merged

function printBothParams( $par1, $par2 ) {
	$x = $par1 . $par2;// This should preserve links from both $par1 and $par2. In particular, $par2's links shouldn't override $par1's
	echo $x;
}

printBothParams( $_GET['x'], 'safe' ); // XSS
printBothParams( 'safe', $_GET['x'] ); // XSS
printBothParams( $_GET['x'], $_GET['x'] ); // Two XSSs, one for each argument
