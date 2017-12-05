<?php
function getStuff() {
	return [ $_GET['a'], $_GET['b'] ];
}

list( $foo ) = getStuff();

echo $foo;
