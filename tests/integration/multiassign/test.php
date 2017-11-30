<?php

function test() {
	$a = $_GET['d'];

	$a = 'ok';

	echo $a;
}

function test2() {
	$b = $_GET['d'];
	if ( false ) {
		$b = 'ok';
	}
	echo $b;
}
