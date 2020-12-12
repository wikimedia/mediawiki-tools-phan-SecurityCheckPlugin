<?php

function getArray() : array {
	$ret = [];
	foreach ( $GLOBALS['foo'] as $element ) {
		$ret[] = [ 'safe' => 'safe' ];
		$ret[] = [ 'escaped' => htmlspecialchars( 'copy' ) ];
	}
	return $ret;
}

function doStuff() {
	$arr = getArray();
	htmlspecialchars( $arr[0]['escaped'] ); // DoubleEscaped
	htmlspecialchars( $arr[0]['safe'] ); // Safe
	htmlspecialchars( $arr[150000]['escaped'] ); // DoubleEscaped
	htmlspecialchars( $arr[150000]['safe'] ); // Safe
}
