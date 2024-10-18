<?php

function preserveY( $arg ) {
	return $arg['y'];
}

$y = [ 'x' => $_GET['a'] ];
$z = [ 'x' => $_GET['a'] ];
echo preserveY( [ 'y' => $y, 'z' => $z ] )['x']; // Unsafe, only caused by 7, 4 (and NOT 8)
