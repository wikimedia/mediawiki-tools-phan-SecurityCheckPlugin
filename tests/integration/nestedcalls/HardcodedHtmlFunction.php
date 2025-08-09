<?php

class HardcodedSimpleTaint {
	public static function escapesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
}
