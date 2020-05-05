<?php

function test() {
	$var = 'safe';
	if ( rand() ) {
		$var = $_GET['x']; // Ensure that this line is in the caused-by
	}
	if ( rand() ) {
		$var = 'safe';
	}
	echo $var;
}
