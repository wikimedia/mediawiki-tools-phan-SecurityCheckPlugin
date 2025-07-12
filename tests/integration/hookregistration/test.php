<?php

// Test for all kinds of hook registration

namespace HookRegistration;

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
		$wgHooks['Hook6'][] = '\HookRegistration\functionHandler';
		$wgHooks['Hook7'][] = '\HookRegistration\HookRegistration::staticHandler';
		$wgHooks['Hook8'][] = new self;
		$wgHooks['Hook9'][] = new static;
		$wgHooks['Hook10'][] = new HookRegistration;
		$wgHooks['Hook11'][] = [ new HookRegistration, 'onHook11', $_GET['unsafe'] ];
		$wgHooks['Hook12'][] = [ [ $this, 'myHook12Handler' ] ];
		$wgHooks['Hook13'][] = [ [ $this, 'myHook13Handler' ], $_GET['unsafe'] ];
		$wgHooks['Hook14'][] = [ '\HookRegistration\HookRegistration::staticHandler2' ];
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

function functionHandler( $x ) {
	echo $x;
}
// These are all unsafe.
( new HookRunner )->onHook1( $_GET['foo'] );
( new HookRunner )->onHook2( $_GET['foo'] );
( new HookRunner )->onHook3( $_GET['foo'] );
( new HookRunner )->onHook4( $_GET['foo'] );
( new HookRunner )->onHook5( $_GET['foo'] );
( new HookRunner )->onHook6( $_GET['foo'] );
( new HookRunner )->onHook7( $_GET['foo'] );
( new HookRunner )->onHook8( $_GET['foo'] );
( new HookRunner )->onHook9( $_GET['foo'] );
( new HookRunner )->onHook10( $_GET['foo'] );
( new HookRunner )->onHook11( 'safe' ); // TODO Unsafe because of the extra arg registered in __construct
( new HookRunner )->onHook11( $_GET['foo'] );
( new HookRunner )->onHook12( $_GET['foo'] );
( new HookRunner )->onHook13( 'safe' ); // TODO Unsafe because of the extra arg registered in __construct
( new HookRunner )->onHook13( $_GET['foo'] );
( new HookRunner )->onHook14( $_GET['foo'] );
( new HookRunner )->onHook15( $_GET['foo'] );
( new HookRunner )->onHook16( $_GET['foo'] );
