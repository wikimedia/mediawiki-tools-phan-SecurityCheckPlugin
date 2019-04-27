<?php
function doEvil1( $arg ) {
	$var = '';
	if ( rand() ) {
		$var = $_GET['baz'];
	}
	echo $var;
}



$gvar1 = '';
if ( rand() ) {
	$gvar1 = $_GET['baz'];
}
echo $gvar1;



if ( rand() ) {
	$gvar2 = $_GET['baz'];
}
echo $gvar2;




function doEvil2( $arg ) {
	$var = '';
	while ( rand() ) {
		$var = $_GET['baz'];
	}
	echo $var;
}



$gvar3 = '';
while ( rand() ) {
	$gvar3 = $_GET['baz'];
}
echo $gvar3;



while ( rand() ) {
	$gvar4 = $_GET['baz'];
}
echo $gvar4;
