<?php

namespace SkinJson;

interface Hook1Hook {
	public function onHook1( $x, $y, $z );
}
interface Hook2Hook {
	public function onHook2( &$arg );
}

class HookRunner implements Hook1Hook, Hook2Hook {
	public function onHook1( $x, $y, $z ) {
	}

	public function onHook2( &$arg ) {
	}
}
