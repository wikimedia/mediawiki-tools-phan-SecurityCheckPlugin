<?php
function doEvil( $arg ) {
	echo $arg;
	require $arg;
	unserialize( $arg );
	mysqli_query( new mysqli, $arg );
}

doEvil( $_GET['d'] );
