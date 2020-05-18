<?php

$unsafe = $_GET['d'];

foreach ( $unsafe as $k => $v ) {
	echo $k;
	echo $v;
}

function inFunctionScope() {
	$unsafe = $_GET['d'];

	foreach ( $unsafe as $k => $v ) {
		echo $k;
		echo $v;
	}
}
