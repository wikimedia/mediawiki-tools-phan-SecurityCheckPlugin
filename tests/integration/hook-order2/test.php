<?php

namespace HookOrder2;

use MediaWiki\HookContainer\HookContainer;

$hookContainer = new HookContainer();

$hookContainer->register( 'Something', 'HookOrder2\SecondClass::hook1' );
$hookContainer->register( 'Something', 'HookOrder2\SecondClass::hook2' );

$tainted = $_GET['user'];
$output = '';
( new HookRunner() )->onSomething( $tainted, $output );
echo $output;// XSS caused by 10, 12, 21 (in this order)
htmlspecialchars( $output ); // DoubleEscaped caused by 12, 18 (in this order)

class SecondClass {
	public static function hook1( $arg1, &$arg2 ) {
		$arg2 = htmlspecialchars( '' );
	}
	public static function hook2( $arg1, &$arg2 ) {
		$arg2 = $arg1;// This must not be in the caused-by for line 14
	}
}
