<?php

$arr = [
	'safe' => 'safe',
	'unsafe' => $_GET['x']
];
$arr['alsounsafe'] = $_GET['foo'];
$arr['escaped'] = htmlspecialchars( 'foo' );

echo $arr['safe']; // Safe
echo $arr['unsafe']; // Unsafe
echo $arr['alsounsafe']; // Unsafe
echo $arr['escaped']; // Safe
htmlspecialchars( $arr['escaped'] ); // DoubleEscaped
echo $arr; // Unsafe
htmlspecialchars( $arr ); // DoubleEscaped

$arr['unsafe'] .= 'safe';
echo $arr['unsafe']; // Still unsafe

echo $arr[ $GLOBALS['unknown'] ]; // Unsafe, can't tell what the key is
htmlspecialchars( $arr[ $GLOBALS['unknown'] ] ); // DoubleEscaped, can't tell what the key is

$arr[$GLOBALS['unknown']] = $_GET['tainted'];

echo $arr['safe']; // Unsafe, could've become tainted
echo $arr['escaped']; // Unsafe, could've become tainted
echo $arr['somekeynotseenabove']; // Unsafe, we have no data for this key so we use the overall
htmlspecialchars( $arr['escaped'] ); // DoubleEscaped, could still be the original value
echo $arr[ $GLOBALS['unknown'] ]; // Unsafe, can't determine the key
htmlspecialchars( $arr[ $GLOBALS['unknown'] ] ); // DoubleEscaped, can't determine the key

$arr = [
	'unsafe' => 'now is safe',
	'escaped' => $_GET['now-unsafe']
];

echo $arr['unsafe']; // Safe
echo $arr['escaped']; // Unsafe
htmlspecialchars( $arr['escaped'] ); // Safe
echo $arr; // Unsafe
htmlspecialchars( $arr ); // Safe

$arr = [
	'safe' => 'safe',
	'escaped' => htmlspecialchars( 'foo' ),
	'unsafe' => $_GET['unsafe']
];

$arr += [
	'safe' => $_GET['baz'], // Ignored
	'unsafe' => 'safe', // Ignored
	'newunsafe' => $_GET['anotherunsafe']
];

echo $arr['safe']; // Safe
echo $arr['unsafe']; // Unsafe
echo $arr['newunsafe']; // Unsafe
echo $arr; // Unsafe

$arr = [
	'safe' => $_GET['now-unsafe'],
	'unsafe' => 'now-safe'
] + $arr;

echo $arr['safe']; // Unsafe
echo $arr['unsafe']; // Safe
echo $arr['newunsafe']; // Unsafe
echo $arr; // Unsafe

$arr = [ 'safe' => 'safe', 'unsafe' => $_GET['foo'] ];
$tempVar = $arr;
$tempVar['newSafe'] = 'safe';
$tempVar['newUnsafe'] = $_GET['unsafe'];

echo $tempVar['safe']; // Safe
echo $tempVar['unsafe']; // Unsafe
echo $tempVar['newSafe']; // Safe
echo $tempVar['newUnsafe']; // Unsafe
echo $tempVar; // Unsafe

$stuff = [
	$_GET['evil'],
	htmlspecialchars( $_GET['foo'] ),
	'safe' => 'safe',
	'unsafe' => $_GET['unsafe'],
	$_GET['baz']
];
echo $stuff[1]; // Safe
echo htmlspecialchars( $stuff[0] ); // Safe
echo $stuff['safe']; // Safe
echo $stuff['unsafe']; // Unsafe
echo $stuff[2]; // Unsafe
echo $stuff; // Unsafe
htmlspecialchars( $stuff ); // DoubleEscaped

$foo = [];
$foo['bar']['unsafe'] = $_GET['x'];
$foo['foo']['safe'] = 'safe';
echo $foo; // Unsafe
echo $foo['bar']; // Unsafe
echo $foo['bar']['unsafe']; // Unsafe
echo $foo['foo']; // Safe
echo $foo['foo']['safe']; // Safe
