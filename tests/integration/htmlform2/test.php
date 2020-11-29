<?php

function buildRadios() {
	$unsafeOptions1 = getUnsafeKeysUnsafeValue();
	$unsafe1 = [
		'type' => 'radio', // Unsafe
		'options' => $unsafeOptions1
	];

	$unsafeOptions2 = getUnsafeKeysSafeValue();
	$unsafe2 = [
		'type' => 'radio', // Unsafe
		'options' => $unsafeOptions2
	];

	$safeOptions1 = getSafeKeysUnsafeValue();
	$safe1 = [
		'type' => 'radio', // Safe
		'options' => $safeOptions1
	];

	$safeOptions2 = getSafeKeysSafeValue();
	$safe2 = [
		'type' => 'radio', // Safe
		'options' => $safeOptions2
	];

	$safeOptions3 = getLiteralKeysUnsafeValue();
	$safe3 = [
		'type' => 'radio', // Safe
		'options' => $safeOptions3
	];
}

function getUnsafeKeysUnsafeValue() {
	$ret = [];
	foreach ( $_GET['baz'] as $foo ) {
		$ret[$foo] = $foo;
	}
	return $ret;
}

function getUnsafeKeysSafeValue() {
	$ret = [];
	foreach ( $_GET['baz'] as $foo ) {
		$ret[$foo] = 'safe';
	}
	return $ret;
}

function getSafeKeysUnsafeValue() {
	$ret = [];
	foreach ( $_GET['baz'] as $foo ) {
		$ret[htmlspecialchars($foo)] = $foo;
	}
	return $ret;
}

function getSafeKeysSafeValue() {
	$ret = [];
	foreach ( $_GET['baz'] as $foo ) {
		$ret[htmlspecialchars($foo)] = 'foo';
	}
	return $ret;
}

function getLiteralKeysUnsafeValue() {
	$ret = [];
	foreach ( $_GET['baz'] as $foo ) {
		$ret['foobar'] = $foo;
	}
	return $ret;
}
