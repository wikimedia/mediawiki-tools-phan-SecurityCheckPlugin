<?php

function testGoto() {
	// We don't even try to analyze gotos, but make sure we don't crash when one is encountered.
	$x = 99;
	start:
	echo "$x bottles of beer\n";
	$x--;
	if ( $x > 0 ) {
		goto start;
	}
}