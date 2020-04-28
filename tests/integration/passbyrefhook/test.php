<?php

class Hooks {
	public static function run( $hookName, $args ) {
	}
}

class MyClass {
	public static function myHandler( &$arg ) {
		$arg = $_GET['x'];
	}
}

function testEvil() {
	$var1 = '';
	Hooks::run( 'MyHook', [ &$var1 ] );
	echo $var1;
}
