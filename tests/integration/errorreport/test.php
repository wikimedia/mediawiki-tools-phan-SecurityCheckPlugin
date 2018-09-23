<?php
function foo() {
	$a = [ 'dog', 'cat' ];
	$a[] = $_GET['goat'];

	echo "The animals are: " . implode( ', ', $a );
	echo "The animals are: " . htmlspecialchars( implode( ', ', $a ) );
	$s = "The animals are: " . htmlspecialchars( $a[2] );
	echo $s . $_GET['evil'];

	$b = getEvil();
	echo $b;
	echo implode( '!', $b );
}

function getEvil() {
	return [ $_GET['foo'] ];
}
