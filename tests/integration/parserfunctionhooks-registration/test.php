<?php

// Test various ways to register parser function hooks.
// NOTE: Keep in sync with parsertaghooks-registration

use MediaWiki\Parser\Parser;

class ParserFunctionHooksRegistration {
	private $ownInstance;

	public function register() {
		$parser = new Parser;

		// String callables
		$parser->setFunctionHook( 'string1', 'parserFunctionHookGlobalFunctionGlobalNamespace' );
		$parser->setFunctionHook( 'string2', '\ParserFunctionHooksRegistration\parserFunctionHookGlobalFunctionNamespaced' );
		$parser->setFunctionHook( 'string3', '\ParserFunctionHooksRegistration\parserFunctionHookSafeIfNamespacedUnsafeIfGlobal' );
		$parser->setFunctionHook( 'string4', __CLASS__ . '::staticMethodForStringClass' );
		$parser->setFunctionHook( 'string5', ParserFunctionHooksRegistration::class . '::staticMethodForStringFull' );
		$parser->setFunctionHook( 'string6', self::class . '::staticMethodForStringSelf' );
		$parser->setFunctionHook( 'string7', '\ParserFunctionHooksRegistration::staticMethodForStringLiteral' );

		// Array callables
		$parser->setFunctionHook( 'array1', [ __CLASS__, 'staticMethodForArrayClass' ] );
		$parser->setFunctionHook( 'array2', [ ParserFunctionHooksRegistration::class, 'staticMethodForArrayFull' ] );
		$parser->setFunctionHook( 'array3', [ self::class, 'staticMethodForArraySelf' ] );
		$parser->setFunctionHook( 'array4', [ 'ParserFunctionHooksRegistration', 'staticMethodForArrayString' ] );
		$parser->setFunctionHook( 'array5', [ $this, 'nonstaticMethodForArrayDirect' ] );
		$this->ownInstance = $this;
		$parser->setFunctionHook( 'array6', [ $this->ownInstance, 'nonstaticMethodForArrayIndirect' ] );

		// Inline closure
		$parser->setFunctionHook( 'closure1', function ( Parser $parser, $arg1 ) {
				return [ $_GET['a'], 'isHTML' => true ]; // Unsafe
		} );
		// Indirect closure
		$indirectClosure = function ( Parser $parser, $arg ) {
			return [ $_GET['a'], 'isHTML' => true ]; // Unsafe
		};
		$parser->setFunctionHook( 'closure2', $indirectClosure );
		$parser->setFunctionHook( 'closure3', fn ( Parser $parser, $arg ) => [ $_GET['a'], 'isHTML' => true ] ); // TODO: Unsafe
	}

	// All the handlers below should be equal, and all unsafe.

	public static function staticMethodForStringClass( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ]; // TODO: Unsafe
	}

	public static function staticMethodForStringFull( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ]; // TODO: Unsafe
	}

	public static function staticMethodForStringSelf( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ]; // TODO: Unsafe
	}

	public static function staticMethodForStringLiteral( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ];
	}

	public static function staticMethodForArrayClass( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ];
	}

	public static function staticMethodForArrayFull( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ];
	}

	public static function staticMethodForArraySelf( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ];
	}

	public static function staticMethodForArrayString( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ];
	}

	public static function nonstaticMethodForArrayDirect( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ];
	}

	public static function nonstaticMethodForArrayIndirect( Parser $parser, $arg ) {
		return [ $_GET['a'], 'isHTML' => true ];
	}
}
