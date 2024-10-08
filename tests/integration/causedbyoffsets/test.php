<?php

function execUnusedValue( $par ) {
	$b = $_GET['b'];

	$v = [ 'a' => $par , 'b' => $b];

	echo $v['a'];
}
execUnusedValue( $_GET['t'] ); // Unsafe

function execUnusedKey( $par ) {
	$b = $_GET['b'];

	$v = [ 'a' => $par , $b => 'foo'];

	echo $v['a'];
}
execUnusedKey( $_GET['t'] ); // Unsafe
