<?php

function withStatic( $x ) {
	static $var = 1;
	'@phan-debug-var-taintedness $var';
	echo $var;
	$var = $x;
}
withStatic( 'safe' );
withStatic( $_GET['x'] ); // TODO Unsafe
withStatic( 'safe2' ); // Ideally safe
