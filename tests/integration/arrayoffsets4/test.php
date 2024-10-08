<?php

function getArray1() {
	return [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ];
}

$var1 = getArray1();
echo $var1['safe']; // Safe
echo $var1['unsafe']; // Unsafe
echo $var1; // Unsafe

function getArray2() {
	if ( rand() ) {
		return [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ];
	} else {
		return [ 'safe' => $_POST['a'], 'unsafe' => $_GET['b'] ];
	}
}

$var2 = getArray2();
echo $var2['safe']; // Unsafe (NOT caused by line 14)
echo $var2['unsafe']; // Unsafe
echo $var2; // Unsafe


function getArray3() {
	if ( rand() ) {
		return [ 'safe' => 'safe', 'unsafe' => $_GET['b'] ];
	} else {
		return [ 'safe' => 'alsosafe', 'unsafe' => 'safe' ];
	}
}

$var3 = getArray3();
echo $var3['safe']; // Safe
echo $var3['unsafe']; // Unsafe
echo $var3; // Unsafe


function getArray4() {
	$ret = [ 'safe' => 'safe' ];
	if ( rand() ) {
		$ret['safe'] = $_GET['unsafe'];
	} else {
		$ret['reallysafe'] = 'safe';
	}
	return $ret;
}

$var4 = getArray4();
echo $var4['safe']; // Unsafe
echo $var4['reallysafe']; // Safe
echo $var4; // Unsafe


$bad = [ $_GET['a'] ];
echo join( $bad ); // Unsafe
echo implode( ',', $bad ); // Unsafe
