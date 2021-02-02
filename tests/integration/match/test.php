<?php

$sureUnsafe = match ( $GLOBALS['unknown'] ) {
	'foo' => $_GET['a'],
	'bar' => $_GET['b']
};
echo $sureUnsafe; // XSS

$sureSafe = match ( $GLOBALS['unknown'] ) {
	'foo' => 'safe1',
	'bar' => 'safe2'
};
echo $sureSafe; // Safe

$mixed = match ( $GLOBALS['unknown'] ) {
	'foo' => $_GET['unsafe'],
	'bar' => 'safe'
};
echo $mixed; // XSS

function constantMatch() {
	$var = 'foo';
	$unsafe = match ( $var ) {
		'foo' => $_GET['unsafe'],
		'bar' => 'safe'
	};
	echo $unsafe; // XSS

	$safe = match ( 'foo' ) {
		'foo' => 'safe',
		'bar' => $_GET['unsafe']
	};
	echo $safe; // TODO Ideally safe, still limited upstream
}

function armDoesntMatter() {
	$safe = match ( $GLOBALS['x'] ) {
		$_GET['foo'] => 'safe',
		$_GET['bar'] => 'alsosafe'
	};
	echo $safe; // Safe
}
