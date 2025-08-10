<?php

use MediaWiki\Parser\Parser;

class ParserTagHooks {
	public function register() {
		$parser = new Parser;

		// Unsafe
		$parser->setHook( 'unsafe1', [ $this, 'returnUnsafeDirect' ] );
		$parser->setHook( 'unsafe2', [ $this, 'returnUnsafeIndirect' ] );
		$parser->setHook( 'unsafe3', [ $this, 'returnFirstArg' ] );
		$parser->setHook( 'unsafe4', [ $this, 'returnFirstArgIndirect' ] );
		$parser->setHook( 'unsafe5', [ $this, 'returnSecondArg' ] );

		// Safe
		$parser->setHook( 'safe1', [ $this, 'returnLiteralDirect' ] );
		$parser->setHook( 'safe2', [ $this, 'returnLiteralIndirect' ] );
	}

	// Helpers
	private static function returnLiteral( $ignoredParam ) {
		return 'safe';
	}

	private static function returnEvil() {
		return $_GET['a'];
	}

	// Unsafe hooks

	public static function returnUnsafeDirect( $content, array $attribs ) {
		return $_GET['a'];
	}

	public static function returnUnsafeIndirect( $content, array $attribs ) {
		return self::returnEvil();
	}

	public static function returnFirstArg( $content, array $attribs ) {
		return $content;
	}

	public static function returnFirstArgIndirect( $content, array $attribs ) {
		$text = '<div>' . $content . '</div>';
		return $text;
	}

	public static function returnSecondArg( $content, array $attribs ) {
		$val = '';
		if ( isset( $attribs['value'] ) ) {
			$val = ' title="' . $attribs['value'] . '" ';
		}

		return "<div $val>Some text</div>";
	}

	// Safe hooks

	public static function returnLiteralDirect( $content, array $attribs ) {
		return 'safe';
	}

	public static function returnLiteralIndirect( $content, array $attribs ) {
		$ret = '<div>' . self::returnLiteral( $content ) . '</div>';
		return $ret;
	}
}
