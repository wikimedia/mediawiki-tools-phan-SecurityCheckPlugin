<?php

function testDimLinksOverwrite( $x ) {
	$y = [ 'a' => $x ];
	$y['a'] .= 'safe';// This shouldn't overwrite the method links from $x
	echo $y['a'];
}

testDimLinksOverwrite( $_GET['X'] ); // XSS
