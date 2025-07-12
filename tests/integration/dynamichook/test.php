<?php

// See T250371

namespace DynamicHook;

interface DynamicHook {
	public function onDynamic( $x );
}

class HookRunner implements DynamicHook {
	public function onDynamic( $x ) {
	}
}

class CentralAuthTokenSessionProvider {
	public function __construct() {
		global $wgHooks;
		$wgHooks['Dynamic'][] = $this;
	}

	public function onDynamic( $x ) {
		echo $x;
	}
}

( new HookRunner() )->onDynamic( $_GET['foo'] );
