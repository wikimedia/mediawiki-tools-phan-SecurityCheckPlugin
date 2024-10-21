<?php

function rekeyFirstElWithUnsafeDirectAssignment( array $values ): array {
	$firstEl = $values[0];// This line must NOT be in the caused-by.
	$ret = [ $_GET['a'] => $firstEl ];// This line must be in the caused-by.
	return $ret;
}

echo rekeyFirstElWithUnsafeDirectAssignment( [ 'foo', 'bar' ] );

function rekeyFirstElWithUnsafeDimAssignment( array $values ): array {
	$ret = [];
	$firstEl = $values[0];// This line must NOT be in the caused-by.
	$ret[$_GET['a']] = $firstEl;// This line must be in the caused-by.
	return $ret;
}

echo rekeyFirstElWithUnsafeDimAssignment( [ 'foo', 'bar' ] );

function rekeyWithUnsafeUsingForeach( array $values ): array {
	$ret = [];
	foreach ( $values as $value ) {// This line must NOT be in the caused-by.
		$ret[$_GET['a']] = $value;// This line must be in the caused-by.
	}
	return $ret;
}

echo rekeyWithUnsafeUsingForeach( [ 'foo', 'bar' ] );
