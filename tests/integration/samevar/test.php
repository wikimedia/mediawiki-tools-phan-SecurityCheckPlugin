<?php

function foobar( $par ) {
	$value = $par;
	if ( is_null( $value ) ) {
		$value = '';
	}
	$value = htmlspecialchars( $value ); // This is not double escaped

	return $value;
}

$y = $_GET['foo'];
$y = getEscaped( $y );
echo $y;

$z = $_GET['baz'];
// These _are_ double escaped
getEscaped( htmlspecialchars( $z ) );
getEscaped2( htmlspecialchars( $z ) );
getEscaped3( htmlspecialchars( $z ) );

function getEscaped( $x ) {
	$x = htmlspecialchars( $x ); // Safe assignment
	return $x;
}

function getEscaped2( $x ) {
	$y = htmlspecialchars( $x );
	$x = $y; // Safe assignment
	return $x;
}

function getEscaped3( $x ) {
	$x = '';
	$x = htmlspecialchars( $x ); // Safe assignment
	return $x;
}
