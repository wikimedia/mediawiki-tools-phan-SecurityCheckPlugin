<?php
function wfShellExec( $arg ) {
	return "stdout";
}

function doStuff() {
	// Unsafe
	wfShellExec( $_GET['foo'] );
	$command = "echo -n " . $_GET['a'] . ' ' . $_GET['b'];
	wfShellExec( $command );

	// Safe
	wfShellExec( [ $_GET['foo'] ] );
	$args = [
		'echo',
		'-n',
		$_GET['a'],
		$_GET['b']
	];

	wfShellExec( $args );
}
