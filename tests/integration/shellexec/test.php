<?php

shell_exec( $_GET['x'] );

function doExec( $arg ) {
	shell_exec( $arg );
}
doExec( $_GET['x'] );

function pass( $arg ) {
	passthru( $arg, $_ );
}
pass( $_GET['unsafe'] );

function getUnsafe() {
	return $_GET['x'];
}

$y = 'safe';
$x = exec( getUnsafe(), $y, $z );
echo $x;// Note: not caused by line 16, since the return value of exec() is considered tainted regardless of arguments
echo $y; // TODO: This is unsafe.
echo $z;

echo system( getUnsafe(), $_ );// Note: the XSS is not caused by line 16, since the return value of system() is considered tainted regardless of arguments

shell_exec( escapeshellarg( $_GET['foo'] ) ); // Safe
shell_exec( escapeshellcmd( $_GET['foo'] ) ); // Safe
