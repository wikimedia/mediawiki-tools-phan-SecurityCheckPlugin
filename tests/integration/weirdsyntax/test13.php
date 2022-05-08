<?php

function testFloatOffset() {
	$arr = [];
	$key = 1.2;
	// Ensure that the following don't emit a warning on PHP 8.1
	$arr[$key] = 'foo';
	echo $arr[$key];
	echo strlen( [4.5 => 1] );
}
