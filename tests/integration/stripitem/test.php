<?php

class SomeClass {
	public function register() {
		$parser = new MediaWiki\Parser\Parser();

		$parser->setFunctionHook( 'something', [ __CLASS__, 'xyzzy' ] );
		$parser->setFunctionHook( 'something', [ __CLASS__, 'xyzzy2' ] );
	}

	public function xyzzy( MediaWiki\Parser\Parser $parser, $arg1 ) {
		$a = $parser->printArgumentReturnSafe( $arg1 );
		return $a;
	}

	public function xyzzy2( $parser, $arg1 ) {
		$a = $parser->printArgumentReturnSafe( $arg1 );
		return $a;
	}
}
