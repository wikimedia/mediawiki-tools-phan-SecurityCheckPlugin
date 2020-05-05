<?php

/**
 * Test to ensure that we never override the taint in optional branches.
 */

/* Simple if 1 */

$var1 = $_GET['x'];
if ( rand() ) {
	$var1 = htmlspecialchars( $var1 );
}
echo $var1;

/* Simple if 2 */

$var2 = 'x';
if ( rand() ) {
	$var2 = $_GET['x'];
}
echo $var2;

/* Foreach loop */

$var3 = $_GET['x'];
foreach ( $_GET as $_ ) {
	$var3 = htmlspecialchars( $var3 );
}
echo $var3;

/* While loop */

$var4 = 'safe';
while ( rand() === 42 ) {
	$var4 = $_GET['unsafe'];
}
echo $var4;

/* Nested ifs */

$var5 = 'safe';
if ( rand() ) {
	if ( rand() ) {
		$var5 = 'stillsafe';
	} elseif ( rand() ) {
		$var5 = $_GET['x'];
	}
}
if ( rand() ) {
	$var5 = htmlspecialchars( $var5 );
}
echo $var5;

/* Nested mixed */

$var6 = 'safe';
do {
	if ( rand() ) {
		if ( rand() ) {
			$var6 = 'baz';
		} elseif ( rand() ) {
			$var6 = $_GET['x'];
		}
	} else {
		$var6 = htmlspecialchars( $var6 );
	}
} while ( rand() );
echo $var6;
