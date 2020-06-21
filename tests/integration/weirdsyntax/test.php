<?php

/* Test for weird/invalid syntax, to ensure it doesn't crash the plugin */

function wrongArrow( $value ) {
	$array = ['a' -> $value]; // Ensure no crash
}

function inPlaceSpread() {
	$array = [ 1, 2, 3 ];
	$array = [ ...$array ]; // Ensure no crash
}

function numberCall() {
	( 2 )(); // No crash
	( 2.3 )();  // No crash
}

