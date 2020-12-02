<?php

preg_match( $_GET['foo'], 'baz' ); // Unsafe
preg_match( '/ba+z/', $_GET['foo'] ); // Safe

/**
 * @param-taint $x exec_regex
 */
function doMatch( $x ) {
}
doMatch( $_GET['foo'] );// Unsafe

echo preg_replace( $_GET['foo'], 'baz', 'fooooooooo' ); // ReDoS
echo preg_replace( $_GET['foo'], $_GET['x'], 'fooooooooo' ); // ReDoS + XSS

$bad = $_GET['tainted'];
preg_match_all( "/$bad/", 'foo' );// Unsafe
$good = preg_quote( $bad, '/' );
preg_match_all( "/$good/", 'foo' );// Safe
