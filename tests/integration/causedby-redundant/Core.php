<?php

// NOTE: This file should be analyzed AFTER CentralAuth.php, so don't rename it

class MapCacheLRU {
	private $cache = [];
	public function get( $key ) {
		return $this->cache[$key];
	}
	public function setField( $key, $field, $value ) {
		$this->cache[$key][$field] = $value;
	}
}

function wfMessage( $key, ...$params ) : Message {
}

class LogEventsList  {
	public static function showLogExtract( &$out ) {
		Html::rawElement( 'div', [ 'class' => 'mw-warning-logempty' ], wfMessage( 'logempty' )->parse() );
	}
}


class Message {
	public function parse() {
	}
	public function text() {}
}

class OutputPage {
	public function addHTML( $text ) {
	}
}


class Html {

	public static function rawElement( $element, $attribs = [], $contents = '' ) {
		return self::openElement( $element, $attribs ) . $contents;
	}

	public static function element( $element, $attribs = '' ) {
		return self::rawElement( $element, $attribs );
	}

	public static function openElement( $element, $attribs = [] ) {
		return "<$element" . self::expandAttributes( self::dropDefaults( $attribs ) );
	}

	private static function dropDefaults( array $attribs ) {
		return $attribs;
	}

	public static function expandAttributes( array $attribs ) {
		foreach ( $attribs as $key => $value ) {
			htmlspecialchars( $value );
		}
		return htmlspecialchars( $attribs );
	}
}
