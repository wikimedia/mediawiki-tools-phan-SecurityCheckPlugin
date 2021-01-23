<?php

function echoProp( &$properties ) {
	$prop = $properties->foo; // No crash here
	echo $prop; // TODO: XSS (and no FalsePositive!), but it doesn't work even without the pass-by-ref
}
$x = (object)[ 'foo' => $_GET['a'] ];
echoProp( $x );
