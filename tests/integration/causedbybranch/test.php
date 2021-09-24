<?php

// This test is based on the Html class. Its goal is to ensure correct caused-by lines when the
// execution can branch multiple times.

function innerFunc( array $par ) {
	$ret = '';
	foreach ( $par as $key => $value ) {
		if ( rand() ) {
			$arrayValue = [];
			foreach ( $value as $k => $v ) {
				if ( $v ) {
					foreach ( $v as $part ) {
						$arrayValue[] = $part;
					}
				} else {
					$arrayValue[] = $k;
				}
			}
			$value = $arrayValue;
		}

		$encValue = htmlspecialchars( $value );
		$ret .= $key . $encValue;
	}
	return $ret;
}

function outerFunc( array $par ) {
	return innerFunc( $par );
}

htmlspecialchars( outerFunc( [] ) );
outerFunc( [ htmlspecialchars( 'foo' ) ] );
