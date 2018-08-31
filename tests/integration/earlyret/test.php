<?php

function foo( $a ) {
	if ( $a ) {
		return htmlspecialchars( 'something' );
	}
	return $_GET['evil'];
}

echo foo( false );
