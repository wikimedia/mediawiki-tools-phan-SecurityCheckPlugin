<?php

function testListAssignment( array $arr ) {
	list( $a, $b ) = $arr;
	echo "$a and $b";
}

$safe = [ 'foo', 'bar' ];
$unsafe = [ $_GET['Good'], $_GET['b'] ];
$mixed = [ 'foo', $_GET['Good'] ];

testListAssignment( $safe );
testListAssignment( $unsafe );
testListAssignment( $mixed );

list( $safe, $unsafe ) = $mixed;


echo $safe;
echo $unsafe;

testListAssignmentAndEcho( $_GET['baz'] );

function testListAssignmentAndEcho( $ev ) {
	list( $stillevil ) = [ $ev ];
	echo $stillevil;
}
