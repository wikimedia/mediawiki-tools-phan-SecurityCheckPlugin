<?php

// See T250371

class Hooks {
	public static function run( $event, array $args = [] ) {
	}
}

class CentralAuthTokenSessionProvider {
	public function __construct() {
		global $wgHooks;
		$wgHooks['BeforePageDisplay'][] = $this;
	}

	public function onBeforePageDisplay( $x ) {
		echo $x;
	}
}

Hooks::run( 'BeforePageDisplay', [ $_GET['foo'] ] );
