<?php

$arr = [ 'safe' => 'safe', 'unsafe' => $_GET['unsafe'] ];
foreach ( $arr as $k1 => $v1 ) {
	echo $v1; // Unsafe
	if ( $k1 === 'safe' ) {
		echo $v1; // Ideally safe, but unsafe because we don't retroactively track $v
		echo $arr[$k1]; // Safe
	}
	if ( $k1 === 'unsafe' ) {
		echo $v1; // Unsafe
		echo $arr[$k1]; // Unsafe
	}
}

$arr = [
	'safe' => 'safe',
	'unsafe' => $_GET['a'],
	'switched' => [
		'safe' => $_GET['b'],
		'unsafe' => 'safe'
	]
];

$new1 = $new2 = [];
foreach ( $arr as $k2 => $v2 ) {
	echo $v2; // Unsafe
	echo $v2['safe']; // Unsafe
	echo $v2['unsafe']; // Unsafe (can be a subkey of 'unsafe' via $_GET['a'])
	if ( $k2 === 'switched' ) {
		$new1 = $v2;
		$new2 = $arr[$k2];
	}
}

echo $new1['safe']; // Unsafe
echo $new1['unsafe']; // Unsafe (it thinks it can be a subkey of 'unsafe' via $_GET['a']. This is ruled out by the if check, but that's too complicated)
echo $new2['safe']; // Unsafe
echo $new2['unsafe']; // Safe


$arr = [
	'switched' => [
		'safe' => $_GET['b'],
		'unsafe' => 'safe'
	],
	$GLOBALS['unknown'] => [// NOTE: The key here might be 'switched', thus overwriting the values above
		'safe' => 'safe',
		'unsafe' => $_GET['taint']
	]
];

$new1 = $new2 = [];
foreach ( $arr as $k3 => $v3 ) {
	echo $v3; // Unsafe
	echo $v3['safe']; // Unsafe
	echo $v3['unsafe']; // Unsafe
	if ( $k3 === 'switched' ) {// This doesn't exclude the case with unknown key, so everything's unsafe
		$new1 = $v3;
		$new2 = $arr[$k3];
	}
}

echo $new1['safe']; // Unsafe
echo $new1['unsafe'];  // Unsafe due to unknown key
echo $new2['safe']; // Unsafe
echo $new2['unsafe']; // Unsafe due to unknown key

$arr = [
	'normal' => [
		'safe' => 'safe',
		'unsafe' => $_GET['a'],
	],
	'switched' => [
		'safe' => $_GET['b'],
		'unsafe' => 'safe'
	]
];

$new1 = $new2 = [];
foreach ( $arr as $k4 => $v4 ) {
	echo $v4; // Unsafe
	echo $v4['safe']; // Unsafe
	echo $v4['unsafe']; // Unsafe
	if ( $k4 === 'switched' ) {
		$new1 = $v4;
		$new2 = $arr[$k4];
	}
}

echo $new1['safe']; // Unsafe
echo $new1['unsafe']; // Ideally safe, but unsafe because we don't retroactively track $v
echo $new2['safe']; // Unsafe
echo $new2['unsafe']; // Safe
