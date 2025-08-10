<?php

// Test analysis of hook handlers.

namespace TestHooks;

use MediaWiki\HookContainer\HookContainer;

class Handlers {
	public static function replaceRefArgWithUnsafe( &$arg ) {
		$arg = $_GET['x'];
	}
	public static function replaceRefArgWithSafe( &$arg ) {
		$arg = 'safe';
	}
	public static function echoArg( $arg ) { // Unsafe due to line 48, but not 47
		echo $arg;
	}
	public static function escapeArg( $arg ) {// DoubleEscaped due to line 52
		echo htmlspecialchars( $arg );
	}
	public static function swapArgs( &$first, &$second ) {
		[ $first, $second ] = [ $second, $first ];
	}
}

$hookContainer = new HookContainer();
$hookContainer->register( 'ReplaceRefArgWithUnsafe', '\\TestHooks\\Handlers::replaceRefArgWithUnsafe' );
$hookContainer->register( 'ReplaceRefArgWithSafe', '\\TestHooks\\Handlers::replaceRefArgWithSafe' );
$hookContainer->register( 'EchoArg', '\\TestHooks\\Handlers::echoArg' );
$hookContainer->register( 'EscapeArg', '\\TestHooks\\Handlers::escapeArg' );
$hookContainer->register( 'SwapArgs', '\\TestHooks\\Handlers::swapArgs' );

function testReplaceRefArgWithUnsafe() {
	$var = '';
	( new HookRunner() )->onReplaceRefArgWithUnsafe( $var );
	echo $var; // Unsafe due to line 11
}

function testReplaceRefArgWithSafe() {
	$var = $_GET['a'];
	( new HookRunner() )->onReplaceRefArgWithSafe( $var );
	echo $var; // TODO: Safe
}

function testEchoArg() {
	( new HookRunner() )->onEchoArg( 'safe' );
	( new HookRunner() )->onEchoArg( $_GET['a'] );
}

function testEscapeEscapedArg() {
	( new HookRunner() )->onEscapeArg( htmlspecialchars( 'foo' ) );
}

function testSwapArgs() {
	$safe = 'safe';
	$unsafe = $_GET['a'];
	( new HookRunner() )->onSwapArgs( $safe, $unsafe );
	echo $safe; // Now unsafe
	echo $unsafe; // TODO: Now safe
}
