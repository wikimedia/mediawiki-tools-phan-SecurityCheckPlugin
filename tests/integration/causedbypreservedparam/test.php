<?php

function appendSafeToParam( $x ) {
	$y = $x;
	$y .= 'safe';
	return $y;
}
$taintedArg1 = $_GET['a'];
echo appendSafeToParam( $taintedArg1 ); // Lines 4, 6, 8


function appendUnsafeToParam( $x ) {
	$y = $x;
	$y .= $_GET['x'];
	return $y;
}
$taintedArg2 = $_GET['a'];
echo appendUnsafeToParam( $taintedArg2 ); // Lines 13, 14, 15, 17
$safeArg = 'safe';
echo appendUnsafeToParam( $safeArg ); // Lines 14, 15
