<?php

/**
 * Here $arg is never used outside of branches, so we need to link the variable inside branches
 * to the one outside, and make sure we get an XSS warning.
 */
function overwriteArg1( $arg ) {
	if ( rand() ) {
		$arg = $_GET['x'] . $arg;
	}
	if ( rand() ) {
		$arg = htmlspecialchars( $arg );
	}
	echo $arg;
}

function overwriteArg2( $arg ) {
	if ( rand() ) {
		if ( rand() ) {
			if ( rand() ) {
				$arg = $_GET['x'] . $arg;
			}
		} else {
			$arg = 'safe';
		}
	}
	if ( rand() ) {
		$arg = htmlspecialchars( $arg );
	}
	echo $arg;
}

/* Same as above, but with a passbyref */
function manipulateArg( &$ref ) {
	if ( rand() ) {
		$ref = $_GET['x'];
	}
	if ( rand() ) {
		$ref = htmlspecialchars( $ref );
	}
}

$ref = 'x';
manipulateArg( $ref );
echo $ref;

function mainFunc() {
	escapeArg( htmlspecialchars( '' ) );
}

function escapeArg( $text ) {
	if ( rand() ) {
		$text = 'x' .  $text;
	}
	htmlspecialchars( $text );
}
