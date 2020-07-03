<?php

$arr1 = [
	'safe' => 'safe',
	'unsafe' => $_GET['x']
];

$arr1 = $arr1 + [ $GLOBALS['unknown'] => $_GET['baz'] ]; // This won't override existing values

echo $arr1['safe']; // TODO Ideally safe, but we don't distinguish possibly-tainted vs possibly-untainted keys (and the assignment above will contribute to the overall taint regardless)
echo $arr1['unsafe']; // Still unsafe
echo $arr1['whatisthiskey']; // Unsafe
echo $arr1; // Unsafe


$arr1 = [
	'safe' => 'safe',
	'unsafe' => $_GET['x']
];

$arr1 = [ $GLOBALS['unknown'] => $_GET['baz'] ] + $arr1; // This will override existing values

echo $arr1['safe']; // Unsafe
echo $arr1['unsafe']; // Still unsafe
echo $arr1['whatisthiskey']; // Unsafe
echo $arr1; // Unsafe


$arr1 = [
	'safe' => 'safe',
	'unsafe' => $_GET['x']
];
$arr2 = [
	'safe' => $_GET['baz'],
	'new' => $_GET['unsafe']
];

$arr = $arr1 + $arr2;
echo $arr['safe']; // Safe
echo $arr[$GLOBALS['xyz']]; // Unsafe
echo $arr; // Unsafe

$arr1 = [
	'part1' => [
		'safe' => 'safe',
		'unsafe' => $_GET['x']
	],
	'part2' => [
		'safe' => 'safe',
		'unsafe' => $_GET['x']
	]
];
$arr2 = [
	'safe' => $_GET['unsafe'],
	'unsafe' => 'safe',
	'newsafe' => 'safe',
	'newunsafe' => $_GET['unsafe']
];
$arr3 = [
	'part1' => [
		'safe' => $_GET['unsafe']
	],
	'part2' => [
		'unsafe' => 'safe'
	]
];

$arr1['part1'] += $arr2;
echo $arr1['part1']['safe']; // Safe
echo $arr1['part1']['unsafe']; // Unsafe
echo $arr1['part1']['newsafe']; // Safe
echo $arr1['part1']['newunsafe']; // Unsafe
echo $arr1['part1']; // Unsafe
echo $arr1; // Unsafe

$arr1['part2'] = $arr2;
echo $arr1['part2']['safe']; // Unsafe
echo $arr1['part2']['unsafe']; // Safe
echo $arr1['part2']['newsafe']; // Safe
echo $arr1['part2']['newunsafe']; // Unsafe
echo $arr1['part2']; // Unsafe
echo $arr1; // Unsafe

$arr1['part1'] += $arr3['part1'];
echo $arr1['part1']['safe']; // Safe

$arr1['part2'] += $arr3['part2'];
echo $arr1['part1']['unsafe']; // Unsafe

$arr1 = [
	'safe' => 'safe',
];
$arr2 = [
	$GLOBALS['dunno'] => $_GET['baz']
];

$arr = $arr1 + $arr2;
echo $arr['safe']; // TODO Ideally safe, but we don't track this very precisely
echo $arr['bah']; // Unsafe
echo $arr; // Unsafe

$arr1 = [];
$arr2 = [
	'inner' => [
		'safe' => 'safe',
		'unsafe' => $_GET['baz']
	]
];

$arr1['foo'] += $arr2['inner'];
echo $arr1; // Unsafe
echo $arr1['foo']; // Unsafe
echo $arr1['foo']['safe']; // Safe
echo $arr1['foo']['unsafe']; // Unsafe


$arr1 = [];
$arr1['foo']['baz']['bar'] += $arr2['inner'];
echo $arr1; // Unsafe
echo $arr1['foo']; // Unsafe
echo $arr1['foo']['baz']; // Unsafe
echo $arr1['foo']['baz']['bar']; // Unsafe
echo $arr1['foo']['baz']['bar']['safe']; // Safe
echo $arr1['foo']['baz']['bar']['unsafe']; // Unsafe


$arr1 = [];
$arr1['foo']['baz']['bar'] += $arr2;
echo $arr1; // Unsafe
echo $arr1['foo']; // Unsafe
echo $arr1['foo']['baz']; // Unsafe
echo $arr1['foo']['baz']['bar']; // Unsafe
echo $arr1['foo']['baz']['bar']['inner']; // Unsafe
echo $arr1['foo']['baz']['bar']['inner']['safe']; // Safe
echo $arr1['foo']['baz']['bar']['inner']['unsafe']; // Unsafe
