<?php

function testMultiassign1() {
	$a = $_GET['d'];

	$a = 'ok';

	echo $a;
}

function testMultiassign2() {
	$b = $_GET['d'];
	if ( false ) {
		$b = 'ok';
	}
	echo $b;
}
