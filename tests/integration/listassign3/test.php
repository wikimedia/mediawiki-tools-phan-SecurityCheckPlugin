<?php

function withList() {
	$list = [
		'safe',
		$_GET['tainted']
	];
	[ $safe, $tainted ] = $list;
	echo $safe;
	echo $tainted; // Unsafe
}

function withMap() {
	$map = [
		'safe' => 'safe',
		'tainted' => $_GET['tainted']
	];
	[ 'safe' => $safe, 'tainted' => $tainted ] = $map;
	echo $safe;
	echo $tainted; // Unsafe
}

function withMixed() {
	$mixed = [
		'safe' => 'safe',
		'tainted' => $_GET['tainted']
	];
	[ $safe, $tainted ] = $mixed; // This is an error, but we know the keys, so let's assume everything is safe
	echo $safe;
	echo $tainted; // Safe
}

function withMixed2() {
	$mixed = [
		'safe' => 'safe',
		'tainted' => $_GET['tainted'],
		$GLOBALS['whoami'] => $_GET['alsotainted']
	];
	[ $unsafe ] = $mixed; // Also an error, BTW
	echo $unsafe; // Unsafe
}

function unknownKeysWithNumeric() {
	$map = [
		$GLOBALS['unknown1'] => 'safe',
		$GLOBALS['unknown2'] => $_GET['tainted']
	];
	[ 'foo' => $unknown1, 'bar' => $unknown2 ] = $map; // This is an error
	echo $unknown1; // Unsafe
	echo $unknown2; // Unsafe
}

function nested() {
	$map = [
		'normal' => [
			'safe' => 'safe',
			'unsafe' => $_GET['foo']
		],
		'reverse' => [
			'safe' => $_GET['foo'],
			'unsafe' => 'foo'
		]
	];
	$wrong = [];
	[ 'normal' => $wrong['reverse'], 'reverse' => $wrong['normal'] ] = $map;
	echo $wrong; // Unsafe
	echo $wrong['normal']; // Unsafe
	echo $wrong['reverse']; // Unsafe
	echo $wrong['normal']['safe']; // Unsafe
	echo $wrong['normal']['unsafe']; // Safe
	echo $wrong['reverse']['safe']; // Safe
	echo $wrong['reverse']['unsafe']; // Unsafe
}

function veryMuchNested() {
	$map = [
		'normal' => [
			'safe' => 'safe',
			'unsafe' => $_GET['foo']
		],
		'reverse' => [
			'safe' => $_GET['foo'],
			'unsafe' => 'foo'
		]
	];

	[ 'normal' => [ 'safe' => $safeNor, 'unsafe' => $unsafeNor ], 'reverse' => [ 'safe' => $safeRev, 'unsafe' => $unsafeRev ] ] = $map;
	echo $safeNor;
	echo $unsafeNor; // Unsafe
	echo $safeRev; // Unsafe
	echo $unsafeRev;
}

function differentLevels1() {
	$arr = [
		'unsafe' => [
			'safe' => '',
			'unsafe' => $_GET['a']
		],
		'safe' => $_GET['a']
	];

	[ 'unsafe' => $var ] = $arr;
	echo $var; // Unsafe
	echo $var['safe']; // Safe
	echo $var['unsafe']; // Unsafe
}

function differentLevels2() {
	$arr = [
		'safe' => [
			'safe' => '',
			'unsafe' => $_GET['a']
		],
		'unsafe' => 'safe'
	];

	[ 'safe' => $var ] = $arr;
	echo $var; // Unsafe
	echo $var['safe']; // Safe
	echo $var['unsafe']; // Unsafe
}
