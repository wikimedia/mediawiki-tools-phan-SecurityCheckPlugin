<?php

class MyClass {
	public $prop;

	function test() {
		$this->prop = [ 'safe' => 'safe', 'unsafe' => $_GET['baz'] ];
		echo $this->prop['safe']; // Safe
		echo $this->prop['unsafe']; // Unsafe
		$this->prop['unsafe'] = 'safe';
		echo $this->prop['unsafe']; // Unsafe, because we don't override for props
		$this->prop['esc'] = htmlspecialchars( 'foo' );
		htmlspecialchars( $this->prop['safe'] ); // Safe
		$this->prop = [ 'unsafe' => 'safe', 'safe' => 'alsosafe' ];
		echo $this->prop['unsafe']; // Still unsafe due to no overriding the taint
		$this->prop += [ 'safe' => $_GET['baz'], 'foo' => $_GET['tainted'] ];
		echo $this->prop['safe']; // Safe, because it's ignored by the plus above
		echo $this->prop['unsafe']; // Unsafe
	}
}

$arr = [];
$arr['foo'][$GLOBALS['unknown']]['bar'] = [ 'safe' => 'safe', 'unsafe' => $_GET['baz'] ];
echo $arr; // Unsafe
echo $arr['foo']; // Unsafe
echo $arr['foo']['whatshere']; // Unsafe due to unknown keys
echo $arr['foo']['whatshere']['foo']; // Safe TODO Should this be unknown?
echo $arr['foo']['whatshere']['bar']; // Unsafe
echo $arr['foo']['whatshere']['bar']['safe']; // Safe
echo $arr['foo']['whatshere']['bar']['unsafe']; // Unsafe

$arr = [
	$GLOBALS['unknown'] => [
		'a1' => [
			'safe' => 'safe',
			$GLOBALS['alsounknown'] => $_GET['baz']
		]
	]
];
echo $arr; // Unsafe
echo $arr['dunno']; // Unsafe

$arr1 = [
	'l1' => [
		'safe' => 'safe',
		'unsafe' => $_GET['a']
	],
	$GLOBALS['dunno'] => [
		'safe' => 'safe',
		'unsafe' => $_GET['a']
	]
];

$arr2 = [
	'l1' => [
		'safe' => $_GET['a'],
	],
	$GLOBALS['unkn'] => [
		'safe' => 'safe',
		'unsafe' => $_GET['a']
	]
];

$arr = $arr1 + $arr2;
echo $arr; // Unsafe
echo $arr['l1'];// Unsafe
echo $arr['l1']['safe'];// Safe
echo $arr['l1']['unsafe'];// Unsafe
echo $arr['foo'];// Unsafe
echo $arr['foo']['safe']; // Safe

$arr = [
	null => 'safe'
];
echo $arr[null]; // Safe
$arr[null] = $_GET['unsafe'];
echo $arr[null]; // Unsafe
echo $arr['']; // Unsafe, because PHP casts null to the empty string (both here and in the taint-check code that stores key taintedness)

$arr = [
	15 => [
		'safe' => 'safe',
		'unsafe' => $_GET['a']
	],
	42 => [
		'safe' => 'safe',
		'unsafe' => $_GET['a']
	]
];

echo $arr; // Unsafe
echo $arr[15]; // Unsafe
echo $arr[15]['safe']; // Safe
echo $arr[15]['unsafe']; // Unsafe

$arr[15]['safe'] .= $_GET['foo'];
echo $arr[15]['safe']; // Unsafe
$arr[15]['unsafe'] .= htmlspecialchars( 'foo' );
echo $arr[15]['unsafe']; // Unsafe
htmlspecialchars( $arr[15]['unsafe'] ); // DoubleEscaped
