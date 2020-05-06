<?php

/* Test to ensure that issues are only reported once for multiline expressions */

function wlhLink( $text ) {
	$text = htmlspecialchars( $text );
	$links = [ // No issue here
		htmlspecialchars(
			$text
		),
	];
}

function getFilterPanel() {
	return str_replace( // No issue here
		htmlspecialchars( htmlspecialchars( $_GET['x'] ) ),
		'foo', 'bar'
	);
}

$gVar = htmlspecialchars( 'x' );
$foo = [ // No issue here
	htmlspecialchars( $gVar )
];
