<?php

// Regression test for when a function returns one of its ref params

function returnRef( &$par ) {
	return $par . $_GET['x'];
}

$f = 'safe';
echo returnRef( $f );
