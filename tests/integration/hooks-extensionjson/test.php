<?php

// Test hook registration from extension.json. See also the hooks-skinjson test for a reduced test suite using skin.json

namespace HooksExtensionJson;

function globalFunction( $unsafe ) {
	echo $unsafe;
}

class HandlerClass {
	public static function staticMethod( $unsafe ) {
		echo $unsafe;
	}

	public function onHookUsingHookHandlers( $unsafe ) {
		echo $unsafe;
	}

	public function onHookUsingBothMethodNameAndHookHandlers( $unsafe ) {
		echo $unsafe;
	}

	public static function additionalHandler( $unsafe ) {
		echo $unsafe;
	}

	public function onThisHookIsDeprecated( $unsafe ) {
		echo $unsafe;
	}

	public function onName__With__Many__Symbols_Really( $unsafe ) {
		echo $unsafe;
	}
}

$hookRunner = new HookRunner();

$hookRunner->onHookWithGlobalHandler( $_GET['tainted'] );
$hookRunner->onHookWithStaticHandler( $_GET['tainted'] );
$hookRunner->onHookUsingHookHandlers( $_GET['tainted'] );
$hookRunner->onHookUsingBothMethodNameAndHookHandlers( $_GET['tainted'] );
$hookRunner->onThisHookIsDeprecated( $_GET['tainted'] );
$hookRunner->onName__With__Many__Symbols_Really( $_GET['tainted'] );
