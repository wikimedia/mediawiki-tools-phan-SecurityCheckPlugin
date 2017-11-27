<?php

namespace foo;

use \Hooks;

class SomeClass {
	public function register() {
		Hooks::register( 'Something', 'SecondClass::hook1' );
		Hooks::register( 'Something', [ 'SecondClass::hook2' ] );
		Hooks::register( 'Something', [ [ 'SecondClass::hook3' ] ] );
		Hooks::register( 'Something', [ 'SecondClass::hook4', 'someArg' ] );
		Hooks::register( 'Something', [ '\foo\wfSomeGlobal', 'someArg' ] );

		$something = new SecondClass;
		Hooks::register( 'Something', [ $something, 'hook5' ] );
		Hooks::register( 'Something', [ new SecondClass, 'hook6' ] );
		Hooks::register( 'Something', new SecondClass );
	}

}

function wfSomeGlobal( $arg1, &$arg2, $extraArg = '' ) {
}

class SecondClass {
	public static function hook1( $arg1, &$arg2, $extraArg = '' ) {
	}
	public static function hook2( $arg1, &$arg2, $extraArg = '' ) {
	}
	public static function hook3( $arg1, &$arg2, $extraArg = '' ) {
	}
	public static function hook4( $arg1, &$arg2, $extraArg = '' ) {
	}
	public static function hook5( $arg1, &$arg2, $extraArg = '' ) {
	}
	public static function hook6( $arg1, &$arg2, $extraArg = '' ) {
	}
	public function onSomething( $arg1, &$arg2, $extraArg = '' ) {
	}
}
