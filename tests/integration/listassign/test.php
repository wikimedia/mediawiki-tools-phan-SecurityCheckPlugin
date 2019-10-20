<?php

function foo( array $arr ) {
	list( $a, $b ) = $arr;
	echo "$a and $b";
}

$safe = [ 'foo', 'bar' ];
$unsafe = [ $_GET['Good'], $_GET['b'] ];
$mixed = [ 'foo', $_GET['Good'] ];

foo( $safe );
foo( $unsafe );
foo( $mixed );

list( $safe, $unsafe ) = $mixed;

// This is safe, but is not reported as such because array elements share taintedness
echo $safe;
echo $unsafe;

evil( $_GET['baz'] );

function evil( $ev ) {
	list( $stillevil ) = [ $ev ];
	echo $stillevil;
}
