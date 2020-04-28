<?php

// Regression test for assigning a ref argument to something else

function echoLocalCopy( &$par ) {
	$myVar = $par;
	echo $myVar;
}

$l = $_GET['x'];
echoLocalCopy( $l );
