<?php

function echoAllPathUnknown( $x ) {
	echo $x;
	require $x[rand()];
}

/** @return-taint html */
function getHTML(): string {
	return 'foo';
}

/** @return-taint path */
function getPath(): string {
	return 'foo';
}
