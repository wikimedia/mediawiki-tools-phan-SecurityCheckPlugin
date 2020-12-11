<?php

$arr1 = [
	'safe' => 'safe',
	'unsafe' => $_GET['x'],
	'inner' => [
		'safe' => 'safe',
		'unsafe' => $_GET['unsafe']
	]
];

$arr2 = [
	'safe2' => 'safe',
	'unsafe2' => $_GET['z'],
	'esc2' => htmlspecialchars( 'x' ),
	'inner' => [
		'safe' => 'safe',
		'unsafe' => $_GET['unsafe']
	]
];
$arr3 = $arr2;

$arr1[$GLOBALS['X']] = $_GET['tainted'];
echo $arr1['safe']; // Unsafe
$arr2[$GLOBALS['y']] = $arr1[$GLOBALS['z']];
echo $arr2['safe2']; // Unsafe

$arr1 = [
	'safe' => 'safe',
	'inner' => [
		'safe' => 'safe',
		'unsafe' => $_GET['unsafe']
	]
];

$arr1['inner'][$GLOBALS['xxx']] = $arr3['inner']['safe']; // TODO This line adds a safe value, so it should be in caused-by lines only for keys, but right now we don't distinguish these cases
echo $arr1['inner']['safe']; // Safe
echo $arr1['inner']['unsafe']; // Unsafe
echo $arr1['inner']; // Unsafe
$arr1['inner'][$GLOBALS['yyy']] = $arr3['inner']['unsafe'];
echo $arr1['inner']['safe']; // Unsafe
echo $arr1['inner']['unsafe']; // Unsafe
echo $arr1['inner']; // Unsafe
echo $arr1['safe']; // Safe
echo $arr1; // Unsafe

$arr1 = [ 'safe' => 'safe' ];
$arr1 += [ 'unsafe' => $_GET['unsafe'] ];
echo $arr1['safe']; // Safe
echo $arr1['unsafe']; // Unsafe
echo $arr1; // Unsafe
$foo = $arr1;
echo $foo['safe']; // Safe
echo $foo['unsafe']; // Unsafe
echo $foo; // Unsafe

$arr = [ 'unsafe' => $_GET['baz'] ];
$arr['unsafe'] = 'safe';
echo $arr; // Safe

$arr = [
	'safe' => 'safe',
	'esc' => htmlspecialchars( 'x' )
];
htmlspecialchars( $arr[ $GLOBALS['unk'] ] ); // DoubleEscaped


$arr = [
	'safe' => 'safe',
	'inner' => []
];
$arr['inner'][$GLOBALS['unknown']] = $_GET['a'];
echo $arr['safe']; // Safe


$arr = [
	'l1' => [
		'safe' => 'safe',
		'unsafe' => $_GET['baz']
	]
];
$arr['l1']['unsafe'] = 'safe';
echo $arr['l1']['unsafe']; // Safe


$arr = [
	'l1' => []
];
$arr['l1']['notexists'] = $_GET['baz'];
$arr['l1']['notexists']['notexists2'] = $_GET['bar'];
$arr['l1']['notexists']['notexists2']['notexists3'] = 'safe';
$arr['l1']['notexists4']['notexists5']['notexists6'] = 'safe';
echo $arr; // Unsafe
echo $arr['l1']; // Unsafe
echo $arr['l1']['notexists']; // Unsafe
echo $arr['l1']['notexists']['notexists2']; // Unsafe
echo $arr['l1']['notexists']['notexists2']['notexists3']; // Safe
echo $arr['l1']['notexists4']; // Safe
echo $arr['l1']['notexists4']['notexists5']; // Safe
echo $arr['l1']['notexists4']['notexists5']['notexists6']; // Safe
