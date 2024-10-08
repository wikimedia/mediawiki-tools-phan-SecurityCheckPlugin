<?php

$arr1 = [
	'safe' => 'safe',
	'unsafe' => $_GET['x'],
	'esc' => htmlspecialchars( 'x' )
];

$arr2 = [
	'safe2' => 'safe',
	'unsafe2' => $_GET['z'],
	'esc2' => htmlspecialchars( 'x' )
];

$arr1['safe'] = $arr2['unsafe2'];
echo $arr1['safe']; // Unsafe
echo $arr1; // Unsafe

$arr1['safe'] = $arr2['safe2'];
echo $arr1['safe']; // Safe
$arr2['unsafe2'] = $arr1['safe'];
// $arr2 is all safe now
echo $arr2['unsafe2']; // Safe
echo $arr2; // Safe

$arr1['stillsafe'] = $arr2[ $GLOBALS['unknown'] ];
echo $arr1['stillsafe']; // Safe

htmlspecialchars( $arr2[ $GLOBALS['alsounknown'] ] ); // DoubleEscaped, can only be caused by $arr2['esc2'] at line 9 (and not line 3)

$arr1 = [
	'l1' => [
		'l2' => [
			'safe' => 'safe',
			'unsafe' => $_GET['baz']
		]
	]
];
$arr2 = [
	'l1' => [
		'l2' => [
			'safe' => 'safe',
			'unsafe' => $_GET['baz']
		]
	]
];

echo $arr1['l1']['l2']['safe']; // Safe
echo $arr1['l1']['l2']['unsafe']; // Unsafe
echo $arr1['l1']['l2']; // Unsafe
echo $arr1['l1']; // Unsafe
echo $arr1; // Unsafe

$arr1['l1'] = $arr2['l1']['l2'];
echo $arr1['l1']['safe']; // Safe
echo $arr1['l1']['unsafe']; // Unsafe
$arr1['l1']['unsafe'] = 'now_safe';
echo $arr1['l1']['unsafe']; // Safe
echo $arr1['l1']; // Safe

// Array append is not handled very well (phan itself will not infer keys here), so
// we treat it as unknown key.
$arr3 = [];
$arr3[] = $_GET['foo'];
$arr3[] = 'safe';
$arr3[] = htmlspecialchars( 'foo' );

echo $arr3[0]; // Unsafe
echo $arr3[1]; // Unsafe, but ideally safe (TODO)
htmlspecialchars( $arr3[2] ); // DoubleEscaped
echo $arr3; // Unsafe
htmlspecialchars( $arr3 ); // DoubleEscaped

$arr1 = [ 'unsafe' => $_GET['baz'] ];
$arr2 = [ 'safe' => 'safe' ];
$arr1['unsafe'] = $arr2['safe'];
echo $arr1['unsafe']; // Safe
echo $arr1; // Safe


$arr0 = $arr1 = [
	'l1' => [
		'l2' => [
			'safe' => 'safe',
			'unsafe' => $_GET['baz']
		]
	]
];
$arr2 = [
	'l1' => [
		'l2' => [
			'safe' => 'safe',
			'unsafe' => $_GET['baz'],
		]
	]
];

$arr1['l1']['l2'] += $arr2[$GLOBALS['baz']];
echo $arr1; // Unsafe
echo $arr1['l1']['l2']; // Unsafe
echo $arr1['l1']['l2']['safe']; // Safe
echo $arr1['l1']['l2']['unsafe']; // Unsafe

$arr1 = $arr0;
$arr1[$GLOBALS['foo']] += $arr2[$GLOBALS['baz']];
echo $arr1; // Unsafe
echo $arr1['dunno']; // Unsafe
echo $arr1['l1']; // Unsafe
echo $arr1['l1']['l2']; // Unsafe
echo $arr1['l1']['l2']['safe']; // Safe
echo $arr1['l1']['l2']['unsafe']; // Unsafe

$arr1 = $arr0;
$arr1['l1'][$GLOBALS['foo']] += $arr2['l1']['l2'];
echo $arr1; // Unsafe
echo $arr1['dunno']; // Safe, unknown key but no unknown keys were assigned
echo $arr1['l1']; // Unsafe
echo $arr1['l1']['l2']; // Unsafe
echo $arr1['l1']['l2']['safe']; // Safe
echo $arr1['l1']['l2']['unsafe']; // Unsafe
