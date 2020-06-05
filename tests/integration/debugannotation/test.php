<?php

function testFunc() {
	'@phan-debug-var-taintedness $doesnExist';

	$var = 'safe';
	'@phan-debug-var-taintedness $var';
	$var = $_GET['x'];
	'@phan-debug-var-taintedness $var';
	$var1 = escapeshellarg( $var );
	'@phan-debug-var-taintedness $var1';
	$var2 = mysqli_real_escape_string( new mysqli, $var1 );
	'@phan-debug-var-taintedness $var2';
	$var3 = htmlspecialchars( $var2 );
	'@phan-debug-var-taintedness $var3';

	$var4 = 'safe';
	'@phan-debug-var-taintedness $var1, $var2, $var3,$var4';
}
