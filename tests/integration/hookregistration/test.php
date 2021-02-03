<?php
// Test for all kinds of hook registration
class Hooks {
	public static function run( $event, array $args = [] ) {
	}
}

class HookRegistration {
	public function register() {
		global $wgHooks;
		$wgHooks['Hook1'][] = $this;
		$wgHooks['Hook2'][] = [ $this, 'handler2' ];
		$thirdHandler = [ $this, 'handler3' ];
		$wgHooks['Hook3'][] = $thirdHandler;
		$clos = function ( $x ) {
			echo $x;
		};
		$wgHooks['Hook4'][] = $clos;
		$wgHooks['Hook5'][] = function ( $x ) { echo $x; };
		$wgHooks['Hook6'][] = 'globalHandler';
		$wgHooks['Hook7'][] = 'HookRegistration::staticHandler';
		$wgHooks['Hook8'][] = new self;
		$wgHooks['Hook9'][] = new static;
		$wgHooks['Hook10'][] = new HookRegistration;
		$wgHooks['Hook11'][] = [ new HookRegistration, 'onHook11', $_GET['unsafe'] ];
		$wgHooks['Hook12'][] = [ [ $this, 'myHook12Handler' ] ];
		$wgHooks['Hook13'][] = [ [ $this, 'myHook13Handler' ], $_GET['unsafe'] ];
		$wgHooks['Hook14'][] = [ 'HookRegistration::staticHandler2' ];
		$methodName = 'hook15Handler';
		$wgHooks['Hook15'][] = [ $this, $methodName ];
		$thisClass = __CLASS__;
		$wgHooks['Hook16'][] = new $thisClass;
	}

	public function onHook1( $x ) {
		echo $x;
	}
	public function handler2( $x ) {
		echo $x;
	}
	public function handler3( $x ) {
		echo $x;
	}
	public static function staticHandler( $x ) {
		echo $x;
	}
	public function onHook8( $x ) {
		echo $x;
	}
	public function onHook9( $x ) {
		echo $x;
	}
	public function onHook10( $x ) {
		echo $x;
	}
	public function onHook11( $ourArg, $hookArg ) {
		echo $ourArg;
		echo $hookArg;
	}
	public function myHook12Handler( $x ) {
		echo $x;
	}
	public function myHook13Handler( $ourArg, $hookArg ) {
		echo $ourArg;
		echo $hookArg;
	}
	public static function staticHandler2( $x ) {
		echo $x;
	}
	public function hook15Handler( $x ) {
		echo $x;
	}
	public function onHook16( $x ) {
		echo $x;
	}
}

function globalHandler( $x ) {
	echo $x;
}
// These are all unsafe.
Hooks::run( 'Hook1', [ $_GET['foo'] ] );
Hooks::run( 'Hook2', [ $_GET['foo'] ] );
Hooks::run( 'Hook3', [ $_GET['foo'] ] );
Hooks::run( 'Hook4', [ $_GET['foo'] ] );
Hooks::run( 'Hook5', [ $_GET['foo'] ] );
Hooks::run( 'Hook6', [ $_GET['foo'] ] );
Hooks::run( 'Hook7', [ $_GET['foo'] ] );
Hooks::run( 'Hook8', [ $_GET['foo'] ] );
Hooks::run( 'Hook9', [ $_GET['foo'] ] );
Hooks::run( 'Hook10', [ $_GET['foo'] ] );
Hooks::run( 'Hook11', [ 'safe' ] ); // TODO Unsafe because of the extra arg registered in __construct
Hooks::run( 'Hook11', [ $_GET['foo'] ] );
Hooks::run( 'Hook12', [ $_GET['foo'] ] );
Hooks::run( 'Hook13', [ 'safe' ] ); // TODO Unsafe because of the extra arg registered in __construct
Hooks::run( 'Hook13', [ $_GET['foo'] ] );
Hooks::run( 'Hook14', [ $_GET['foo'] ] );
Hooks::run( 'Hook15', [ $_GET['foo'] ] );
Hooks::run( 'Hook16', [ $_GET['foo'] ] );
