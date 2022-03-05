<?php

function foobar( $x ) {
	$y = [ 'a' => $x ];
	$y['a'] .= 'safe';// This shouldn't overwrite the method links from $x
	echo $y['a'];
}

foobar( $_GET['X'] ); // XSS
