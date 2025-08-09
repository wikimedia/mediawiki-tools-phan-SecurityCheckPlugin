<?php

execNumkey( [ 'foo' => $_GET['bar'] ] ); // Safe

execNumkey( [ $_GET['bar'] ] ); // Unsafe

execNumkey( $_GET['bar'] ); // Unsafe

$append1 = [];
$append1[] = $_GET['bar'];
execNumkey( $append1 ); // Unsafe
execNumkey( [ 'safe' => $append1 ] ); // Safe

$append2 = [ $_GET['bar'] ];
$append2[] = 'Something';
execNumkey( $append2 ); // Unsafe

$arrayVar = [ $_GET['d'] => 'foo' ];
execNumkey( $arrayVar ); // Unsafe

$withUnsafeKey = [];
$withUnsafeKey[$_GET['d']] = 'foo';
execNumkey( $withUnsafeKey ); // unsafe because keys are not escaped

$withLiteralIntKey = [ 1 => $_GET['d'] ];
execNumkey( $withLiteralIntKey ); // Unsafe

$b = (int)$_GET['b'];
// Safe: the unsafe value has a string key, and the numeric key is guaranteed to use an integer
execNumkey( [ 'foo' => $_GET['a'], "bar > $b" ] );

$obj = (object)[ 'foo' => $_GET['bar'] ];
execNumkey( [ 'foo2' => $obj->foo ] ); // Safe
$tempVarWithObj = [ 'foo2' => $obj->foo ];
execNumkey( $tempVarWithObj ); // Safe

$safe = [ 'safe' => $_GET['baz'] ];// This line should appear in caused-by
$unsafe = array_values( $safe );
execNumkey( $unsafe ); // SQLi

$newSafe = [ 'safe' => 'safe' ];
$alsoSafe = array_values( $newSafe );
execNumkey( $alsoSafe ); // Safe


$safe2 = [
	'f1' => $thisVariableIsNotSet,
	'f2' => [ $_GET['a'] ],
];
execNumkey( $safe2 ); // Safe (actually a LikelyFalsePositive)

// Test hardcoded taintedness
HardcodedSimpleTaint::execNumkey( [ 'foo' => $_GET['bar'] ] ); // Safe
HardcodedSimpleTaint::execNumkey( [ $_GET['bar'] ] ); // Unsafe
