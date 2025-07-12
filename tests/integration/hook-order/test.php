<?php

// Taken from the registerhook test, ensure that no assumptions are made about call order for hook handlers

namespace HookOrder;

use MediaWiki\HookContainer\HookContainer;

$hookContainer = new HookContainer();

$hookContainer->register( 'Something', 'HookOrder\SecondClass::hook1' );
$hookContainer->register( 'Something', 'HookOrder\SecondClass::hook2' );
$hookContainer->register( 'Something', 'HookOrder\SecondClass::hook3' );

$par1 = '';
$par2 = '';
( new HookRunner() )->onSomething( $par1, $par2 );
echo $par1;
echo $par2;

class SecondClass {
	public static function hook1( &$arg1, &$arg2 ) {
		$arg1 = $_GET['unsafe']; // Unsafe
	}
	public static function hook2( &$arg1, &$arg2 ) {
		$arg1 = 'Foo'; // This should not override the taint!
	}
	public static function hook3( &$arg1, &$arg2 ) {
		$arg2 = $_GET['baz'];
		$arg2 = 'Foo'; // This *should* override the taint from the line above
	}
}
