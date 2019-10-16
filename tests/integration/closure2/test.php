<?php

$evil = $_GET['var'];

$unsafe1 = function () use ( $evil ) {
	echo $evil;
};

$unsafe2 = function () use ( $argv ) {
	echo $argv[0];
};

$safestr = 'foo';

$concat = function ( $val ) use ( $safestr ) {
	echo $val . $safestr;
};

$concat( 'bar' );
$concat( $_GET['baz'] );
