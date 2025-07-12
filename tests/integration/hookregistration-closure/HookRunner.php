<?php

namespace HookRegistrationClosure;

interface Hook1Hook {
	public function onHook1( $arg );
}
interface Hook2Hook {
	public function onHook2( &$arg );
}
interface Hook3Hook {
	public function onHook3( &$arg );
}
interface Hook4Hook {
	public function onHook4( &$arg );
}

class HookRunner implements Hook1Hook, Hook2Hook, Hook3Hook, Hook4Hook {
	public function onHook1( $arg ) {
	}

	public function onHook2( &$arg ) {
	}

	public function onHook3( &$arg ) {
	}

	public function onHook4( &$arg ) {
	}
}
