<?php

$arr = [
	'safe' => 'safe',
	'unsafe' => $_GET['tainted']
];

if ( rand() ) {
	$arr['unsafe'] = 'safe';
	echo $arr; // Safe
	echo $arr['unsafe']; // Safe
}
echo $arr; // Unsafe
echo $arr['unsafe']; // Unsafe

if ( true ) {
	$arr['unsafe'] = htmlspecialchars( $arr['unsafe'] );
}
echo $arr; // Safe
htmlspecialchars( $arr['unsafe'] ); // DoubleEscaped


$arr = [
	'safe' => 'safe',
	'unsafe' => $_GET['tainted']
];

if ( rand() ) {
	$arr['safe'] = $_GET['unsafe'];
	echo $arr['safe']; // Unsafe
} else {
	$arr['unsafe'] = 'safe';
	echo $arr['unsafe']; // Safe
}

echo $arr['safe']; // Unsafe
echo $arr['unsafe']; // Unsafe
echo $arr; // Unsafe
