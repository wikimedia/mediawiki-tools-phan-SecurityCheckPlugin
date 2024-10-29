<?php

// Regression test for a pass-by-ref parameter that depends on an optional parameter, to verify that the plugin
// doesn't crash when that parameter isn't passed.

function assignSecondOrSafeToFirst( &$arg, $newValue = 'safe' ) {
	$arg = $newValue;
}

function doTest() {
	$var = '';
	assignSecondOrSafeToFirst( $var );
	echo $var;
}