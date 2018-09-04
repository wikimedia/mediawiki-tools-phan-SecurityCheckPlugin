<?php

class SomeClass {
	public function register() {
		$parser = new Parser;

		$parser->setFunctionHook( 'something', [ __CLASS__, 'xyzzy' ] );
		$parser->setFunctionHook( 'something', [ __CLASS__, 'xyzzy2' ] );
	}

	public function xyzzy( Parser $parser, $arg1 ) {
		$a = $parser->insertStripItem( $arg1 );
		return $a;
	}

	public function xyzzy2( $parser, $arg1 ) {
		$a = $parser->insertStripItem( $arg1 );
		return $a;
	}
}
