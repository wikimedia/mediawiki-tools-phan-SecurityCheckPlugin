<?php

class HardcodedSimpleTaint {
	public static function yesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
}
