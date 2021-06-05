<?php

$myGlobal = 'safe';

function makeUnsafe() {
	global $myGlobal;
	$myGlobal = $_GET['unsafe'];
	htmlspecialchars( $myGlobal ); // Safe
}

function makeSafe() {
	global $myGlobal;
	$myGlobal = htmlspecialchars( $myGlobal );
	echo $myGlobal; // Ideally safe, but with globals it's complicated (see test 'scope')
}
makeUnsafe();
echo $myGlobal; // XSS
htmlspecialchars( $myGlobal ); // DoubleEscaped

$myArrayGlobal = [];

function addToGlobal( $par ) {
	global $myArrayGlobal;
	$myArrayGlobal[] = $par; // TODO Ideally this would be in the caused-by lines
}
addToGlobal( $_GET['foo'] );
echo $myArrayGlobal; // XSS
addToGlobal( htmlspecialchars( 'foo' ) );
htmlspecialchars( $myArrayGlobal ); // DoubleEscaped
echo $myArrayGlobal; // Still XSS
