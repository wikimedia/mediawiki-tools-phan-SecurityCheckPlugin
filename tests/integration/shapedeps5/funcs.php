<?php

function echoAllPathFooKey( $x ) {
	echo $x;
	require $x['foo'];
}

/** @return-taint html */
function getHTML(): string {
	return 'foo';
}

/** @return-taint path */
function getPath(): string {
	return 'foo';
}
