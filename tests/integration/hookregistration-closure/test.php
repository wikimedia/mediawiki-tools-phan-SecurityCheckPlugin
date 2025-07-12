<?php

namespace HookRegistrationClosure;

$wgHooks['Hook1'][] = function ( $arg ) {// XSS
	echo $arg;
};
$wgHooks['Hook2'][] = function ( &$arg ) {
	$arg = $_GET['a'];
};

function registerClosureUses() {
	global $wgHooks;
	$unsafe = $_GET['a'];
	$wgHooks['Hook3'][] = function( &$arg ) use ( $unsafe ) {
		$arg = $unsafe;
	};
}

$unsafeGlobal = $_GET['a'];
$wgHooks['Hook4'][] = function( &$arg ) use ( $unsafeGlobal ) {
	$arg = $unsafeGlobal;
};

( new HookRunner() )->onHook1( $_GET['foo'] );

$hookVar1 = '';
( new HookRunner() )->onHook2( $hookVar1 );
echo $hookVar1; // XSS

$hookVar2 = '';
( new HookRunner() )->onHook3( $hookVar2 );
echo $hookVar2; // TODO: Ideally XSS, but closures with `use` cannot be reanalyzed

$hookVar3 = '';
( new HookRunner() )->onHook4( $hookVar3 );
echo $hookVar3; // TODO: Ideally XSS, but closures with `use` cannot be reanalyzed

