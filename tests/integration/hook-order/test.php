<?php

// Verify that no assumptions are made about call order for hook handlers

namespace HookOrder;

use MediaWiki\HookContainer\HookContainer;

$hookContainer = new HookContainer();

$hookContainer->register( 'DoesNotClearPreviousTaint', 'HookOrder\FirstClass::handler1' );
$hookContainer->register( 'DoesNotClearPreviousTaint', 'HookOrder\FirstClass::handler2' );
$hookContainer->register( 'DoesNotClearPreviousTaint', 'HookOrder\FirstClass::handler3' );

$par1 = '';
$par2 = '';
( new HookRunner() )->onDoesNotClearPreviousTaint( $par1, $par2 );
echo $par1; // Unsafe due to line 23
echo $par2; // Safe

class FirstClass {
	public static function handler1( &$arg1, &$arg2 ) {
		$arg1 = $_GET['unsafe']; // This should cause the XSS above
	}
	public static function handler2( &$arg1, &$arg2 ) {
		$arg1 = 'Foo'; // This should not clear the taint!
	}
	public static function handler3( &$arg1, &$arg2 ) {
		$arg2 = $_GET['baz'];
		$arg2 = 'Foo'; // This *should* override the taint from the line above
	}
}

$hookContainer->register( 'AllTaintTypesAreMerged', 'HookOrder\SecondClass::handler1' );
$hookContainer->register( 'AllTaintTypesAreMerged', 'HookOrder\SecondClass::handler2' );

$tainted = $_GET['user'];
$output = '';
( new HookRunner() )->onAllTaintTypesAreMerged( $tainted, $output );
echo $output;// XSS caused by 37, 39, 48 (in this order)
htmlspecialchars( $output ); // DoubleEscaped caused by 39, 45 (in this order)

class SecondClass {
	public static function handler1( $arg1, &$arg2 ) {
		$arg2 = htmlspecialchars( '' ); // This MUST be in the caused-by for the DoubleEscaped, but NOT for the XSS
	}
	public static function handler2( $arg1, &$arg2 ) {
		$arg2 = $arg1;// This MUST be in the caused-by for the XSS, but NOT for the DoubleEscaped
	}
}
