<?php

// Test special cases for hook registration using closures.

namespace HookRegistrationClosure;

$wgHooks['ReplaceArgWithUnsafe'][] = function ( &$arg ) {
	$arg = $_GET['a'];
};

function registerClosureUses() {
	global $wgHooks;
	$unsafe = $_GET['a'];
	$wgHooks['ReplaceArgWithUsedLocalUnsafe'][] = function( &$arg ) use ( $unsafe ) {
		$arg = $unsafe;
	};
}

$unsafeGlobal = $_GET['a'];
$wgHooks['ReplaceArgWithUsedGlobalUnsafe'][] = function( &$arg ) use ( $unsafeGlobal ) {
	$arg = $unsafeGlobal;
};

$hookVar1 = '';
( new HookRunner() )->onReplaceArgWithUnsafe( $hookVar1 );
echo $hookVar1; // XSS

$hookVar2 = '';
( new HookRunner() )->onReplaceArgWithUsedLocalUnsafe( $hookVar2 );
echo $hookVar2; // TODO: Ideally XSS, but closures with `use` cannot be reanalyzed

$hookVar3 = '';
( new HookRunner() )->onReplaceArgWithUsedGlobalUnsafe( $hookVar3 );
echo $hookVar3; // TODO: Ideally XSS, but closures with `use` cannot be reanalyzed

