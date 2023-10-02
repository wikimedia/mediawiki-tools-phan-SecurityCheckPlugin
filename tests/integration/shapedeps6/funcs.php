<?php

function echoAllPathKeys( $x ) {
	echo $x;
	foreach ( $x as $k => $_ ) {
		require $k;
	}
}

/** @return-taint html */
function getHTML(): string {
	return 'foo';
}

/** @return-taint path */
function getPath(): string {
	return 'foo';
}
