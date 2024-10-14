<?php

$returnXOfFirstYOfSecond = function ( $first, $second ) {
	return $first['x'] . $second['y'];
};

$echoFirstPassThroughSecond = function ( $first, $second ) {
	echo $first;
	return $second;
};

echo call_user_func_array( $returnXOfFirstYOfSecond, [ $_GET['a'], $_GET['b'] ] ); // XSS
echo call_user_func_array( $returnXOfFirstYOfSecond, [ 'safe', $_GET['b'] ] ); // XSS
echo call_user_func_array( $returnXOfFirstYOfSecond, [ $_GET['a'], 'safe' ] ); // XSS
echo call_user_func_array( $returnXOfFirstYOfSecond, [ 'safe', 'safe' ] ); // Safe
echo call_user_func_array( $returnXOfFirstYOfSecond, [ [ 'x' => $_GET['a'] ], 'safe' ] ); // XSS
echo call_user_func_array( $returnXOfFirstYOfSecond, [ [ 'unused' => $_GET['a'] ], 'safe' ] ); // Safe
echo call_user_func_array( $returnXOfFirstYOfSecond, [ 'safe', [ 'y' => $_GET['b'] ] ] ); // XSS
echo call_user_func_array( $returnXOfFirstYOfSecond, [ 'safe', [ 'unused' => $_GET['b'] ] ] ); // Safe


echo call_user_func_array( $echoFirstPassThroughSecond, [ $_GET['a'], $_GET['b'] ] ); // XSS arg1 caused by 8 and here caused by 9
echo call_user_func_array( $echoFirstPassThroughSecond, [ 'safe', $_GET['b'] ] ); // XSS here caused by 9
echo call_user_func_array( $echoFirstPassThroughSecond, [ $_GET['a'], 'safe' ] ); // XSS arg1 caused by 8
echo call_user_func_array( $echoFirstPassThroughSecond, [ 'safe', 'safe' ] ); // Safe