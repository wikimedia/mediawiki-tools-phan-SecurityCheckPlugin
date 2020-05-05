<?php

/**
 * Test to ensure that we never override the taint in optional branches.
 */

function simpleIf1() {
	$var = $_GET['x'];
	if ( rand() ) {
		$var = htmlspecialchars( $var );
	}
	echo $var;
}

function simpleIf2() {
	$var = 'x';
	if ( rand() ) {
		$var = $_GET['x'];
	}
	echo $var;
}

function foreachLoop() {
	$var = $_GET['x'];
	foreach ( $_GET as $_ ) {
		$var = htmlspecialchars( $var );
	}
	echo $var;
}

function whileLoop() {
	$var = 'safe';
	while ( rand() === 42 ) {
		$var = $_GET['unsafe'];
	}
	echo $var;
}

function nestedIf() {
	$var = 'safe';
	if ( rand() ) {
		if ( rand() ) {
			$var = 'stillsafe';
		} elseif ( rand() ) {
			$var = $_GET['x'];
		}
	}
	if ( rand() ) {
		$var = htmlspecialchars( $var );
	}
	echo $var;
}

function nestedMixed() {
	$var = 'safe';
	do {
		if ( rand() ) {
			if ( rand() ) {
				$var = 'baz';
			} elseif ( rand() ) {
				$var = $_GET['x'];
			}
		} else {
			$var = htmlspecialchars( $var );
		}
	} while ( rand() );
	echo $var;
}
