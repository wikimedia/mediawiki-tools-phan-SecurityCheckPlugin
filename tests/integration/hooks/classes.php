<?php

namespace TestHooks;

interface ReplaceRefArgWithUnsafeHook {
	public function onReplaceRefArgWithUnsafe( &$arg );
}
interface ReplaceRefArgWithSafeHook {
	public function onReplaceRefArgWithSafe( &$arg );
}
interface EchoArgHook {
	public function onEchoArg( $arg );
}
interface EscapeArgHook {
	public function onEscapeArg( $arg );
}
interface SwapArgsHook {
	public function onSwapArgs( &$first, &$second );
}

class HookRunner implements
	ReplaceRefArgWithUnsafeHook,
	ReplaceRefArgWithSafeHook,
	EchoArgHook,
	EscapeArgHook,
	SwapArgsHook
{
	public function onReplaceRefArgWithUnsafe( &$arg ) {
	}
	public function onReplaceRefArgWithSafe( &$arg ) {
	}
	public function onEchoArg( $arg ) {
	}
	public function onEscapeArg( $arg ) {
	}
	public function onSwapArgs( &$first, &$second ) {
	}
}
