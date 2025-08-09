<?php

class CustomPDO extends PDO {
}

$customPDO = new CustomPDO();

// PDO::query is an example of a built-in PHP method with hardcoded taintedness.
$customPDO->query( $_GET['query'] ); // SQLi, using taint from parent class (builtin)

// PDO::query is an example of a built-in PHP method which returns a safe value (int) regardless of arguments
echo $customPDO->setAttribute( $_GET['a'], $_GET['b'] ); // Safe


// Make sure that if a subclass method has hardcoded taintedness, that takes precedence over the parent func body.
class HardcodedHtmlParent {
	public static function yesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
}
class HardcodedSimpleTaint extends HardcodedHtmlParent {
	// No declaration of yesArgReturnsEscaped here.
}

echo HardcodedHtmlParent::yesArgReturnsEscaped( $_GET['a'] ); // Safe
echo HardcodedSimpleTaint::yesArgReturnsEscaped( $_GET['a'] ); // XSS
