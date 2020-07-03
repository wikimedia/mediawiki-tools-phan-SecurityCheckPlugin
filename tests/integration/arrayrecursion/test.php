<?php

// Ensure that the following cases doesn't cause infinite recursion

$openTags = $ret = '';
$maybeState = null;
if ( rand() ) {
	$maybeState = [ $ret, $openTags ];
} elseif ( rand() ) {
	list( $ret, $openTags ) = $maybeState;
}

$handler = $GLOBALS['xx'];
$normalizedHandler = $handler;
if ( !is_array( $handler ) ) {
	$normalizedHandler = [ $normalizedHandler ];
}

function getStuff( $unused ) {
	$response = [];
	if ( rand() ) {
		return $response;
	}
	$response = [
		'files' => $response
	];
	return $response;
}

echo getStuff(1)['files'];
