<?php

class HardcodedSimpleTaint {
	public static function escapesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
	public static function yesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
}

class TestSinkShape {
	public static function sinkAll( $x ) {
		// Placeholder: this method is hardcoded.
	}
}