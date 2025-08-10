<?php

// Test hook registration from skin.json. This only covers basic cases, everything else is covered by the
// hooks-extensionjon test.

namespace HooksSkinJson;

class HandlerClass {
	public static function staticMethod( $unsafe ) {
		echo $unsafe;
	}

	public function onHookUsingHookHandlers( $unsafe ) {
		echo $unsafe;
	}
}

$hookRunner = new HookRunner();

$hookRunner->onHookWithStaticHandler( $_GET['tainted'] );
$hookRunner->onHookUsingHookHandlers( $_GET['tainted'] );
