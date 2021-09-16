<?php

function getEvil() {
	$s = Html::openElementXXX( 'table', [] );
	$s .= $_GET['x'];
	return $s;
}

echo getEvil(); // Should have line 5 in its caused-by, and no lines from the Html class.
