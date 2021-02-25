<?php

class MyHookHandler {
	public function onFirstHook( &$arg ) {
		$arg = $_GET['something'];
	}
	public function onSecondHook( $arg ) {
		echo $arg;
	}
	public static function additionalSecondHookHandler( $arg ) {
		echo $arg;
	}
}

function doStuff() {
	$arg1 = '';
	Hooks::run( 'FirstHook', [ &$arg1 ] );
	echo $arg1;
	Hooks::run( 'SecondHook', [ $arg1 ] );
}

class Hooks {
	public static function run( $hookName, $args ) {
	}
}
