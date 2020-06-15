<?php

function doStuff() {
	$sameVar = null;

	if ( rand() ) {
		if ( rand() ) {
			// This assignment is not responsible for any of the issues below
			$sameVar = $_GET['baz'];
		}
	}

	if ( rand() ) {
		$sameVar = $_GET['bar'];
		echo $sameVar;
	}

	$sameVar = $_GET['foo'];
	echo $sameVar;
}

