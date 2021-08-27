<?php

class CustomPDO extends PDO {
}

$customPDO = new CustomPDO();

// PDO::query is an example of a built-in PHP method with hardcoded taintedness.
$customPDO->query( $_GET['query'] ); // SQLi, using taint from parent class (builtin)

// PDO::query is an example of a built-in PHP method which returns a safe value (int) regardless of arguments
echo $customPDO->setAttribute( $_GET['a'], $_GET['b'] ); // Safe


// Here we assume that Html::rawElement is hardcoded in the plugin. We want to make sure that if a subclass method
// has hardcoded taintedness, that takes precedence over the parent func body.
class FakeHtmlParent {
	public static function rawElement( $a, $b, $c ) : string {
		return 'safe?';
	}
}
class Html extends FakeHtmlParent {
	// No declaration of rawElement here.
}

echo FakeHtmlParent::rawElement( $_GET['a'], $_GET['b'], $_GET['c'] ); // Safe
echo Html::rawElement( $_GET['a'], $_GET['b'], $_GET['c'] ); // XSS
