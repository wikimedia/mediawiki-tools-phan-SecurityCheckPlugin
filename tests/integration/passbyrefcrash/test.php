<?php

function echoProp( &$properties ) {
	$prop = $properties->foo; // No crash here
	echo $prop; // XSS (and no FalsePositive ideally)
}
$x = (object)[ 'foo' => $_GET['a'] ];
echoProp( $x );
