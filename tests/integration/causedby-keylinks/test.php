<?php

function execArgAsKey( $arg ) {
	$array[$arg] = 'safe';
	echo $array;
}

execArgAsKey( $_GET['a'] ); // Unsafe caused by 4, 5

function execArgDimAsKey( $arg ) {
	$array = [];
	foreach ( $arg as $dim ) {
		$array[$dim] = 'safe';
	}

	echo $array;
}

execArgDimAsKey( $_GET['a'] ); // Unsafe caused by 12, 13, 16
