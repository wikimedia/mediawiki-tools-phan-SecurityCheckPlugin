<?php

$arr = [
	'safe' => 'safe',
	'unsafe' => $_GET['x']
];
foreach ( $arr as $k => $_ ) {
	echo $k; // Safe
}

$arr[$_GET['x']] = 'foo';
foreach ( $arr as $k => $_ ) {
	echo $k; // Unsafe
}

$arr1 = [
	'safe' => true
];
foreach ( $arr1 + $arr as $k => $_ ) {
	echo $k; // Unsafe
}

$sum = $arr1 + $arr;
foreach ( $sum as $k => $_ ) {
	echo $k; // Unsafe
}

$numk = [
	$_GET['foo']
];
foreach ( $numk as $k => $_ ) {
	echo $k; // Safe
}

$arr2 = [
	'unsafe' => $_GET['z'],
];
foreach ( $arr2 as $k => $_ ) {
	echo $k; // Safe
}
foreach ( $arr2['unsafe'] as $k => $_ ) {
	echo $k; // Unsafe
}

$arr3 = [
	'safe' => 'safe',
	'unsafe' => $_GET['x']
];

$arr3 = $arr3 + [ $_GET['unsafe'] => $_GET['baz'] ];
foreach ( $arr3 as $k => $_ ) {
	echo $k; // Unsafe
}

function getArray1() {
	return [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ];
}
foreach ( getArray1() as $k => $_ ) {
	echo $k; // Safe
}

$arr4 = [
	'l1' => [
		'l11' => [
			'safe' => true
		],
		'l12' => [
			$_GET['unsafe'] => true
		],
		'l13' => [
			'alsosafe' => true
		]
	],
	'l2' => [
		$_GET['unsafel2'] => [
			'safe' => true
		]
	]
];
foreach ( $arr4 as $k => $_ ) {
	echo $k; // Safe
}
foreach ( $arr4['l1'] as $k => $_ ) {
	echo $k; // Safe
}
foreach ( $arr4['l1']['l11'] as $k => $_ ) {
	echo $k; // Safe
}
foreach ( $arr4['l1']['l12'] as $k => $_ ) {
	echo $k; // Unsafe
}
foreach ( $arr4['l2'] as $k => $_ ) {
	echo $k; // Unsafe
}

$arr4[$_GET['u1']] = 'foo';
$arr4['l1'][$_GET['u2']] = [];// TODO Exclude these lines from caused-by lines where appropriate (requires more granularity)
$arr4['l1']['l11'][$_GET['u2']] = [];
foreach ( $arr4 as $k => $_ ) {
	echo $k; // Unsafe
}
foreach ( $arr4['l1'] as $k => $_ ) {
	echo $k; // Unsafe
}
foreach ( $arr4['l1']['l11'] as $k => $_ ) {
	echo $k; // Unsafe
}
foreach ( $arr4['l1']['l12'] as $k => $_ ) {
	echo $k; // Unsafe
}
foreach ( $arr4['l1']['l13'] as $k => $_ ) {
	echo $k; // Safe
}
