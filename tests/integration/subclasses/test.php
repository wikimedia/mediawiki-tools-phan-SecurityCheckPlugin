<?php

// Test that if an interface method is hardcoded, that taintedness will be inherited by all implementations.

interface HardcodedSimpleTaint {
	public static function yesArgReturnsEscaped( $arg );
}

class HardcodedParent implements HardcodedSimpleTaint {
	public static function yesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
}

class HardcodedChild extends HardcodedParent {
}

echo HardcodedChild::yesArgReturnsEscaped( $_GET['name'] ); // XSS. TODO: Should have hardcoded func in caused-by
