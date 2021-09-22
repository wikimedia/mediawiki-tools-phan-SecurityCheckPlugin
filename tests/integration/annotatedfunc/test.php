<?php

/**
 * @return-taint tainted
 */
function withoutReturn() {
}
echo withoutReturn(); // Unsafe

/**
 * @return-taint tainted
 */
function withSafeReturn() {
	return 'safe';
}
echo withSafeReturn(); // Unsafe

/**
 * @param-taint $a none
 */
function safeParamWithEchoWithoutReturn( $a ) {
	echo $a;
}
safeParamWithEchoWithoutReturn( $_GET[ 'a'] ); // Safe

/**
 * @param-taint $a none
 */
function safeParamWithEchoWithReturn( $a ) {
	echo $a;
	return $a;
}
safeParamWithEchoWithReturn( $_GET[ 'a'] ); // Safe
echo safeParamWithEchoWithReturn( $_GET[ 'a'] ); // Safe
