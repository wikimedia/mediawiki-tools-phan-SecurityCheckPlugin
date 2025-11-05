<?php

$val = $_GET['baz'];
$safe = $val + 1;
echo $safe;

$unknown1 = $_GET['unknown1'];
$unsafe = $unknown1 + $_GET['unknown2'];
echo $unsafe;

$arr = (array)$_GET['array'];
$unsafe2 = $arr + [ 'safe' => true ];
echo implode( '-', $unsafe2 );

function testAdditionUnknownType( $unknownType ) {
	$x = $unknownType + $unknownType;
	echo $x;
}
testAdditionUnknownType( $_GET['a'] );

/*
 * What follows is a complicated way to have an empty union type but non-empty taint data.
 * Note that it doesn't work with a simple function (i.e. it must be a method).
 */
class TestAddition {
	/**
	 * @return-taint tainted
	 */
	function getTaintedEmptyUnionType() {
	}
}

$a = new TestAddition;
$unknownType = $a->getTaintedEmptyUnionType();
'@phan-debug-var $unknownType'; // If a future version of phan doesn't infer an empty union type here, the test becomes useless /wrong
$var = $unknownType + $unknownType;
echo $var; // Unsafe

$unsafe = $_GET['unsafe'];
echo $unsafe + 42.2; // Safe
echo $unsafe + 'foobar'; // Safe
echo $unsafe + true; // Safe
echo $unsafe + null; // Safe
