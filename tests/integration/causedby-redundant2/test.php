<?php

function getEvil() {
	$s = CausedByRedundant2::concatFirstAndStringifiedSecond( 'table', [] );
	$s .= $_GET['x'];
	return $s;
}

echo getEvil(); // Should have line 5 in its caused-by, and no lines from the other file.
