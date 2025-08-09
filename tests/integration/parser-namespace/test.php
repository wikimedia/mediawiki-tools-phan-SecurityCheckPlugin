<?php

use \MediaWiki\Parser\Parser;

class TestNamespacedParser {
	public function register() {
		$parser = new Parser;
		$parser->setFunctionHook( 'unsafe1', [ $this, 'outputEvil' ] );
		$parser->setFunctionHook( 'unsafe2', [ $this, 'unsafeHook' ] );
		$parser->setHook( 'unsafe3', [ __CLASS__, 'unsafeHook2' ] );
	}

	public function outputEvil( Parser $parser, $arg1, $arg2 ) {
		echo $arg1 . $arg2; // XSS
		return 'something';
	}

	public function unsafeHook( Parser $parser, $arg ) {
		return [ $arg, 'isHTML' => true ]; // XSS
	}

	public static function unsafeHook2( $content, array $attribs, Parser $parser, PPFrame $frame ) {
		return $content; // XSS
	}
}
