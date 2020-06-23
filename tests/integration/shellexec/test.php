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
echo $x;
echo $y; // TODO: This is unsafe.
echo $z;

echo system( getUnsafe(), $_ );
