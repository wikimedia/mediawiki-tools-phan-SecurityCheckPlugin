<?php

// Make sure "weird" syntax does not crash parser hook detection.

use \MediaWiki\Parser\Parser;

class ParserFunctionHooksNoCrash {
	public function register() {
		$parser = new Parser;

		$parser->setFunctionHook( 'nocrash1', [ $this, 'testUnpack1' ] );
		$parser->setFunctionHook( 'nocrash2', [ $this, 'testUnpack2' ] );
	}

	public function testUnpack1() {
		return [ ...$GLOBALS['return_data'], 'isHTML' => true ];
	}
	public function testUnpack2() {
		return [ 'isHTML' => true, ...$GLOBALS['return_data'] ];
	}
}
