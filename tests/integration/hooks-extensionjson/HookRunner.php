<?php

namespace HooksExtensionJson;

interface HookWithGlobalHandlerHook {
	public function onHookWithGlobalHandler( $arg );
}
interface HookWithStaticHandlerHook {
	public function onHookWithStaticHandler( $arg );
}
interface HookUsingHookHandlersHook {
	public function onHookUsingHookHandlers( $arg );
}
interface HookUsingBothMethodNameAndHookHandlersHook {
	public function onHookUsingBothMethodNameAndHookHandlers( $arg );
}
interface ThisHookIsDeprecatedHook {
	public function onThisHookIsDeprecated( $arg );
}
interface Name__With__Many__Symbols_ReallyHook {
	public function onName__With__Many__Symbols_Really( $arg );
}

class HookRunner implements
	HookWithGlobalHandlerHook,
	HookWithStaticHandlerHook,
	HookUsingHookHandlersHook,
	HookUsingBothMethodNameAndHookHandlersHook,
	ThisHookIsDeprecatedHook,
	Name__With__Many__Symbols_ReallyHook
{
	public function onHookWithGlobalHandler( $arg ) {
	}

	public function onHookWithStaticHandler( $arg ) {
	}

	public function onHookUsingHookHandlers( $arg ) {
	}

	public function onHookUsingBothMethodNameAndHookHandlers( $arg ) {
	}

	public function onThisHookIsDeprecated( $arg ) {
	}

	public function onName__With__Many__Symbols_Really( $arg ) {
	}
}
