<?php

function echoFooKeyPathUnknown( $x ) {
	echo $x['foo'];
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
