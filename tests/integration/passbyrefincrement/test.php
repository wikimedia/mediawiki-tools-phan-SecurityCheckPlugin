<?php

function incVar( &$par ) {
	$par++;
}

$arg = $_GET['x'];
incVar( $arg );
echo $arg; // Safe!
