<?php
function hardcodedArrayOkParam( $arg ) {
	return "stdout";
}

function doStuff() {
	// Unsafe
	hardcodedArrayOkParam( $_GET['foo'] );
	$command = "echo -n " . $_GET['a'] . ' ' . $_GET['b'];
	hardcodedArrayOkParam( $command );

	// Safe
	hardcodedArrayOkParam( [ $_GET['foo'] ] );
	$args = [
		'echo',
		'-n',
		$_GET['a'],
		$_GET['b']
	];

	hardcodedArrayOkParam( $args );
}
