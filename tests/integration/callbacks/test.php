<?php

$taintProvider = function () {
	return $_GET['a'];
};
$escaper = function ( $x ) {
	return htmlspecialchars( $x );
};
$passThrough = function ( $x ) {
	return $x;
};
$echoFirstPassThroughSecond = function ( $x, $y ) {
	echo $x;
	return $y;
};


echo $taintProvider(); // XSS caused by 4
echo call_user_func( $taintProvider ); // XSS caused by 4

$tainted = $taintProvider();

$escaped1 = $escaper( $tainted ); // Safe
echo $escaped1; // Safe
htmlspecialchars( $escaped1 ); // DoubleEscaped caused by 23, 7
$escaper( $escaped1 ); // DoubleEscaped caused by 23, 7

$escaped2 = call_user_func( $escaped1, $tainted ); // Safe
echo $escaped2; // Safe
htmlspecialchars( $escaped2 ); // TODO DoubleEscaped caused by 28, 7
call_user_func( $escaper, $escaped2 ); // TODO DoubleEscaped caused by 28, 7

echo $passThrough( 'Safe' ); // Safe
echo $passThrough( $_GET['a'] ); // XSS caused by 10
echo $passThrough( $escaped1 ); // Safe
echo call_user_func( $passThrough, 'Safe' ); // Safe
echo call_user_func( $passThrough, $_GET['a'] ); // XSS caused by 10
echo call_user_func( $passThrough, $escaped1 ); // Safe

$echoFirstPassThroughSecond( $_GET['a'], 'safe' ); // XSS arg1 caused by 13
$echoFirstPassThroughSecond( 'safe', $_GET['a'] ); // Safe
$echoFirstPassThroughSecond( $_GET['a'], $_GET['a'] ); // XSS arg1 caused by 13
echo $echoFirstPassThroughSecond( $_GET['a'], 'safe' ); // XSS arg1 caused by 13
echo $echoFirstPassThroughSecond( 'safe', $_GET['a'] ); // XSS here caused by 14
echo $echoFirstPassThroughSecond( $_GET['a'], $_GET['a'] ); // XSS arg1 caused by 13 and here caused by 14
call_user_func( $echoFirstPassThroughSecond, $_GET['a'], 'safe' ); // XSS arg1 caused by 13
call_user_func( $echoFirstPassThroughSecond, 'safe', $_GET['a'] ); // Safe
call_user_func( $echoFirstPassThroughSecond, $_GET['a'], $_GET['a'] ); // XSS arg1 caused by 13
echo call_user_func( $echoFirstPassThroughSecond, $_GET['a'], 'safe' ); // XSS arg1 caused by 13
echo call_user_func( $echoFirstPassThroughSecond, 'safe', $_GET['a'] ); // XSS here caused by 14
echo call_user_func( $echoFirstPassThroughSecond, $_GET['a'], $_GET['a'] ); // XSS arg1 caused by 13 and here caused by 14

$passThroughOrEscaper = rand() ? $passThrough : $escaper;
echo $passThroughOrEscaper( $_GET['a'] ); // XSS caused by 10 TODO ideally also caused by 53
echo call_user_func( $passThroughOrEscaper, $_GET['a'] ); // XSS caused by 10 TODO ideally also caused by 53
