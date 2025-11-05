<?php

function testEarlyReturn( $a ) {
	if ( $a ) {
		return htmlspecialchars( 'something' );
	}
	return $_GET['evil'];
}

echo testEarlyReturn( false );
