<?php

/**
 * @param-taint $par tainted
 */
function variadicAnnotatedAsNormal( ...$par ) {
	echo $par;
	return $par;
}
$test1 = variadicAnnotatedAsNormal( $_GET['a'] ); // Safe
echo $test1; // Unsafe, with line 6 in the caused-by

/**
 * @param-taint ...$par tainted
 */
function normalAnnotatedAsVariadic( $par ) {
	echo $par;
	return $par;
}
$test2 = normalAnnotatedAsVariadic( $_GET['a'] ); // Safe
echo $test2; // Unsafe

/**
 * @param-taint $doesntexist tainted
 */
function nonexistent() {
}
