<?php

function testFloatOffset() {
	$arr = [];
	$key = 1.2;
	// Ensure that the following don't emit a warning
	$arr[$key] = 'foo';
	echo $arr[$key];
	echo strlen( [4.5 => 1] );
}
