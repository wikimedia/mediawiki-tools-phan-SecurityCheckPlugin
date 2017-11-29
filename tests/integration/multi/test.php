<?php
function doEvil( $arg ) {
	echo $arg;
	require $arg;
	unerialize( $arg );
	mysql_query( $arg );
}

doEvil( $_GET['d'] );
