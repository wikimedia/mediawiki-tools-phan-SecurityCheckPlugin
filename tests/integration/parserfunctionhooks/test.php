<?php

// Test parser hook handling.

use \MediaWiki\Parser\Parser;

class ParserFunctionHooks {
	public function register() {
		$parser = new Parser;

		// Unsafe
		$parser->setFunctionHook( 'unsafe1', [ $this, 'returnUnsafeHTML' ] );
		$parser->setFunctionHook( 'unsafe2', [ $this, 'returnArgHTML' ] );
		$parser->setFunctionHook( 'unsafe3', [ $this, 'useArgInOtherSink' ] );
		$parser->setFunctionHook( 'unsafe4', [ $this, 'unsafeHookIndirect1' ] );
		$parser->setFunctionHook( 'unsafe5', [ $this, 'unsafeHookIndirect2' ] );
		$parser->setFunctionHook( 'unsafe6', [ $this, 'unsafeHookIndirect3' ] );
		$parser->setFunctionHook( 'unsafe7', [ $this, 'unsafeHookIndirect4' ] );
		$parser->setFunctionHook( 'unsafe8', [ $this, 'unsafeHookIndirect5' ] );

		// Safe
		$parser->setFunctionHook( 'safe1', [ $this, 'returnLiteralHTML' ] );
		$parser->setFunctionHook( 'safe2', [ $this, 'returnUnsafeNotHTML' ] );
		$parser->setFunctionHook( 'safe3', [ $this, 'returnArgNotHTML' ] );
		$parser->setFunctionHook( 'safe4', [ $this, 'safeHookIndirect1' ] );
	}

	// Unsafe hooks

	public function returnUnsafeHTML( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ];
	}

	public function returnArgHTML( Parser $parser, $arg ) {
		return [ $arg, 'isHTML' => true ];
	}

	public function useArgInOtherSink( Parser $parser, $arg ) {
		shell_exec( $arg );
		return 'safe';
	}

	public function unsafeHookIndirect1( Parser $parser, $arg ) {
		$ret = [ $arg, 'isHTML' => true ];
		return $ret; // TODO: Unsafe
	}

	public function unsafeHookIndirect2( Parser $parser, $arg ) {
		if ( rand() ) {
			$ret = [ $arg, 'isHTML' => true ];
		} else {
			$ret = [ $arg, 'isHTML' => false ];
		}
		return $ret; // TODO: Unsafe
	}

	public function unsafeHookIndirect3( Parser $parser, $arg ) {
		$isHTML = rand() === 1;
		return [ $arg, 'isHTML' => $isHTML ];
	}

	public function unsafeHookIndirect4( Parser $parser, $arg ) {
		if ( rand() ) {
			$ret = [ $arg, 'isHTML' => true ];
		} else {
			$ret = $GLOBALS['unresolvable'];
		}
		return $ret; // TODO: Unsafe
	}

	public function unsafeHookIndirect5( Parser $parser, $arg ) {
		$unknown = (bool)$GLOBALS['unknown'];
		// This is considered unsafe, in the spirit of favouring false positives.
		return [ $arg, 'isHTML' => $unknown ];
	}


	// Safe hooks

	public function returnLiteralHTML( Parser $parser, $arg ) {
		return [ 'safe', 'isHTML' => true ];
	}

	public function returnUnsafeNotHTML( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => false ]; // TODO: Safe
	}

	public function returnArgNotHTML( Parser $parser, $arg ) {
		return [ $arg, 'isHTML' => false ]; // TODO: Safe
	}

	public function safeHookIndirect1( Parser $parser, $arg ) {
		if ( rand() ) {
			$ret = [ $arg, 'isHTML' => false ];
		} else {
			$ret = [ 'safe', 'isHTML' => true ];
		}
		// This is safe, because individual branches are safe.
		return $ret;
	}
}
