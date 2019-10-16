<?php

$printUnsafe = function ( $value ) {
	echo $value;
};

$printSafe = function ( $value ) {
	echo htmlspecialchars( $value );
};

$printUnsafe( 'safe' );
$printSafe( 'safe' );

$printUnsafe( $_GET['val'] );
$printSafe( $_GET['val'] );

$printUnsafe( htmlspecialchars( $_GET['val'] ) );
$printSafe( htmlspecialchars( $_GET['val'] ) );
