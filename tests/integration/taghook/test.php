<?php

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

class SomeClass {
	public function register() {
		$parser = new Parser;

		$parser->setHook( 'something', [ __CLASS__, 'evil' ] );
		$parser->setHook( 'something', [ 'SecondClass', 'evilAttribs' ] );
		$parser->setHook( 'something', [ 'SecondClass', 'good' ] );
		$parser->setHook( 'something', [ 'SecondClass', 'good2' ] );
		$parser->setHook( 'indirect', [ __CLASS__, 'wrapper' ] );
	}

	public static function evil( $content, array $attribs, Parser $parser, PPFrame $frame ) {
		$text = '<div class="toccolours">' . $content . '</div>';
		return $text;
	}

	public static function wrapper( $content, array $attribs, Parser $parser, PPFrame $frame ) {
		return self::evil2( $content, $attribs, $parser, $frame );
	}

	public static function evil2( $content, $attribs, $parser, $frame ) {
		return $_GET['foo'];
	}
}

class SecondClass {
	public static function evilAttribs( $content, array $attribs, Parser $parser, PPFrame $frame ) {
		$val = '';
		if ( isset( $attribs['value'] ) ) {
			$val = ' title="' . $attribs['value'] . '" ';
		}

		return "<div $val>Some text</div>";
	}

	public static function good( $content, array $attribs, Parser $parser, PPFrame $frame ) {
		return '<div>' . $parser->recursiveTagParse( $content ) . '</div>';
	}

	public static function good2( $content, $attribs, $parser, $frame ) {
		return '<div>' . $parser->recursiveTagParse( $content ) . '</div>';
	}
}
