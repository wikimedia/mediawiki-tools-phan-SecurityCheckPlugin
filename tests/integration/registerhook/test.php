<?php

namespace RegisterHook;


use MediaWiki\HookContainer\HookContainer;

class SomeClass {
	public function register() {
		global $wgHooks;
		$hookContainer = new HookContainer();

		$hookContainer->register( 'Something', 'RegisterHook\SecondClass::hook1' );
		$hookContainer->register( 'Something', [ 'RegisterHook\SecondClass::hook2' ] );
		$hookContainer->register( 'Something', [ [ 'RegisterHook\SecondClass::hook3' ] ] );
		$hookContainer->register( 'Something', [ 'RegisterHook\SecondClass::hook4', 'someArg' ] );
		$hookContainer->register( 'Something', [ '\RegisterHook\wfSomeGlobal', 'someArg' ] );

		$something = new SecondClass;
		$hookContainer->register( 'Something', [ $something, 'hook5' ] );
		$hookContainer->register( 'Something', [ new SecondClass, 'hook6' ] );
		$hookContainer->register( 'Something', new SecondClass );
		$wgHooks['Something'][] = 'RegisterHook\SecondClass::hook7';
		$GLOBALS['wgHooks']['Something'][] = [ new SecondClass, 'hook8' ];
	}
}

$tainted = $_GET['user'];
$output = '';
$out2 = '';
( new HookRunner() )->onSomething( $tainted, $output, $out2 );
echo $out2;
echo $output; // XSS caused by 25, 28, 44 (in this order)

function wfSomeGlobal( $arg1, &$arg2, $extraArg = '' ) {
}

class SecondClass {
	public static function hook1( $arg1, &$arg2, $extraArg = '' ) {
		$arg2 = htmlspecialchars( $arg1 ); // safe
	}
	public static function hook2( $arg1, &$arg2, &$extraArg ) {
		$extraArg = $_GET['unsafe'];
	}
	public static function hook3( $arg1, &$arg2, $extraArg = '' ) {
		// unsafe -- we assume that the call order is nondeterministic
		$arg2 = $arg1;
	}
	public static function hook4( $arg1, &$arg2, &$extraArg ) {
		// safe, but shouldn't override taint
		$extraArg = 'Foo';
	}
	public static function hook5( $arg1, &$arg2, $extraArg = '' ) {
		// unsafe
		echo $_GET['evil'];
	}
	public static function hook6( $arg1, &$arg2, &$extraArg ) {
		// safe
		$arg1 = $arg2;
		$arg1 .= $extraArg;
	}
	public static function hook7( $arg1, &$arg2, $extraArg = '' ) {
		// unsafe
		print $_GET['evil'];
	}
	public static function hook8( $arg1, &$arg2, $extraArg = '' ) {
		// unsafe
		require $_GET['evil'];
	}
	public function onSomething( $arg1, &$arg2, $extraArg = '' ) {
		// unsafe
		eval( $_GET['evil'] );
	}
}
