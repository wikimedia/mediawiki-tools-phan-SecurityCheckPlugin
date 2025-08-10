<?php

// Test various ways to register parser tag hooks.
// NOTE: Keep in sync with parserfunctionhooks-registration

use MediaWiki\Parser\Parser;

class ParserTagHooksRegistration {
	private $ownInstance;

	public function register() {
		$parser = new Parser;

		// String callables
		$parser->setHook( 'string1', 'parserTagHookGlobalFunctionGlobalNamespace' );
		$parser->setHook( 'string2', '\ParserTagHooksRegistration\parserTagHookGlobalFunctionNamespaced' );
		$parser->setHook( 'string3', '\ParserTagHooksRegistration\parserTagHookSafeIfNamespacedUnsafeIfGlobal' );
		$parser->setHook( 'string4', __CLASS__ . '::staticMethodForStringClass' );
		$parser->setHook( 'string5', ParserTagHooksRegistration::class . '::staticMethodForStringFull' );
		$parser->setHook( 'string6', self::class . '::staticMethodForStringSelf' );
		$parser->setHook( 'string7', '\ParserTagHooksRegistration::staticMethodForStringLiteral' );

		// Array callables
		$parser->setHook( 'array1', [ __CLASS__, 'staticMethodForArrayClass' ] );
		$parser->setHook( 'array2', [ ParserTagHooksRegistration::class, 'staticMethodForArrayFull' ] );
		$parser->setHook( 'array3', [ self::class, 'staticMethodForArraySelf' ] );
		$parser->setHook( 'array4', [ 'ParserTagHooksRegistration', 'staticMethodForArrayString' ] );
		$parser->setHook( 'array5', [ $this, 'nonstaticMethodForArrayDirect' ] );
		$this->ownInstance = $this;
		$parser->setHook( 'array6', [ $this->ownInstance, 'nonstaticMethodForArrayIndirect' ] );

		// Inline closure
		$parser->setHook( 'closure1', function () {
			return $_GET['a']; // Unsafe
		} );
		// Indirect closure
		$indirectClosure = function () {
			return $_GET['a']; // Unsafe
		};
		$parser->setHook( 'closure2', $indirectClosure );
		$parser->setHook( 'closure3', fn () => $_GET['a'] ); // TODO: Unsafe
	}

	// All the handlers below should be equal, and all unsafe.

	public static function staticMethodForStringClass() {
		return $_GET['a']; // TODO: Unsafe
	}

	public static function staticMethodForStringFull() {
		return $_GET['a']; // TODO: Unsafe
	}

	public static function staticMethodForStringSelf() {
		return $_GET['a']; // TODO: Unsafe
	}

	public static function staticMethodForStringLiteral() {
		return $_GET['a'];
	}

	public static function staticMethodForArrayClass() {
		return $_GET['a'];
	}

	public static function staticMethodForArrayFull() {
		return $_GET['a'];
	}

	public static function staticMethodForArraySelf() {
		return $_GET['a'];
	}

	public static function staticMethodForArrayString() {
		return $_GET['a'];
	}

	public static function nonstaticMethodForArrayDirect() {
		return $_GET['a'];
	}

	public static function nonstaticMethodForArrayIndirect() {
		return $_GET['a'];
	}
}
