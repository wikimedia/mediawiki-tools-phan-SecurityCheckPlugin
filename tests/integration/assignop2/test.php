<?php

function opResultPreservesTaint() {
	$unsafe = $_GET['x'];
	echo ( $unsafe .= 'safe' ); // Unsafe

	$x = ( $unsafe .= 'safe' );
	$y = ( $unsafe = $unsafe . 'safe' );
	echo $unsafe; // Unsafe
	echo $x; // Unsafe
	echo $y; // Unsafe
}

function nestedAppend() {
	$unsafe = $_GET['x'];
	$safe = 'safe';
	$safe .= ( $unsafe .= 'safe' );
	echo $unsafe; // Unsafe
	echo $safe; // Unsafe
}

function taintLinksShouldBePreserved( $x ) {
	$y = $x;
	$y .= 'safe'; // This shouldn't clear $y's taint link on $x
	echo $y; // This makes the func call unsafe (as long as we don't clear links)
}

taintLinksShouldBePreserved( $_GET['x'] ); // Unsafe
