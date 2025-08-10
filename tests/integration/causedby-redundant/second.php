<?php

// NOTE: This file should be analyzed AFTER first.php, so watch out if renaming it

namespace CausedbyRedundant;

class UnsafeArrayHolder {
	private $arrayProp = [];
	public function get( $key ) {
		return $this->arrayProp[$key];
	}
	public function setField( $key, $field, $value ) {
		$this->arrayProp[$key][$field] = $value;
	}
}

/** @return-taint tainted */
function getUnsafe(): string {
	return 'tainted';
}
/** @return-taint escaped */
function getEscaped(): string {
	return 'escaped';
}

class SafeMethodClass  {
	public static function safeMethod( &$ignored ) {
		GenericSinks::yesArgReturnsEscaped( getEscaped() );
	}
}


class GenericHTMLSink {
	/** @param-taint $text exec_html */
	public function htmlSink( $text ) {
	}
}


class GenericSinks {
	/**
	 * @param-taint $arg tainted
	 */
	public static function returnArg( $arg ) {
		return 'hardcoded';
	}
	/**
	 * @param-taint $arg escapes_html
	 */
	public static function escapeArgHTML( $arg ) {
		return 'hardcoded';
	}
}
