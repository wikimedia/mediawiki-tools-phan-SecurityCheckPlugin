<?php
function getStuff() {
	return [ $_GET['a'], $_GET['b'] ];
}

list( $foo ) = getStuff();

echo $foo;

function getSafeStuff() {
	return [ 'a', 'b' ];
}

list( $a, $b ) = getSafeStuff();
echo $b;
