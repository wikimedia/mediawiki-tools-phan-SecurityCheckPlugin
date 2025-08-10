<?php

namespace HooksSkinJson;

interface HookWithStaticHandlerHook {
	public function onHookWithStaticHandler( $arg );
}
interface HookUsingHookHandlersHook {
	public function onHookUsingHookHandlers( $arg );
}

class HookRunner implements HookWithStaticHandlerHook, HookUsingHookHandlersHook {
	public function onHookWithStaticHandler( $arg ) {
	}

	public function onHookUsingHookHandlers( $arg ) {
	}
}