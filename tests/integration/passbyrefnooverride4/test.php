<?php

function appendSecondAndThirdToFirst( &$first, $second, $third ) {
	$first .= $second;
	$first .= $third;
}

function doTest() {
	$secondTainted = $_GET['a'];
	$thirdTainted = $_GET['b'];

	$var1 = '';
	appendSecondAndThirdToFirst( $var1, '', '' );
	echo $var1; // Safe

	$var2 = '';
	appendSecondAndThirdToFirst( $var2, $secondTainted, '' );
	echo $var2; // XSS caused by 9, 17, 4 (in this order)

	$var3 = '';
	appendSecondAndThirdToFirst( $var3, '', $thirdTainted );
	echo $var3; // XSS caused by 10, 21, 5 (in this order)

	$var4 = '';
	appendSecondAndThirdToFirst( $var4, $secondTainted, $thirdTainted );
	echo $var4; // XSS caused by 9, 25, 4, 10, 25, 5 (in this order)

	$var5 = $_GET['x'];
	appendSecondAndThirdToFirst( $var5, '', '' );
	echo $var5; // XSS caused by 28

	$var6 = $_GET['x'];
	appendSecondAndThirdToFirst( $var6, $secondTainted, '' );
	echo $var6; // XSS caused by 32, 9, 33, 4 (in this order)

	$var7 = $_GET['x'];
	appendSecondAndThirdToFirst( $var7, '', $thirdTainted );
	echo $var7; // XSS caused by 36, 10, 37, 5 (in this order)

	$var8 = $_GET['x'];
	appendSecondAndThirdToFirst( $var8, $secondTainted, $thirdTainted );
	echo $var8; // XSS caused by 40, 9, 41, 4, 10, 41, 5 (in this order)
}
