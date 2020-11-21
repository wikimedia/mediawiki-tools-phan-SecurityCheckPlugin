<?php

class Hooks {
	public static function run( $event, array $args = [] ) {
	}
}

Hooks::run( 'Test' ); // Ensure this doesn't cause a crash
