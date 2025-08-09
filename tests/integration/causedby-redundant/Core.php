<?php

// NOTE: This file should be analyzed AFTER CentralAuth.php, so don't rename it
use MediaWiki\Message\Message;
class MapCacheLRU {
	private $cache = [];
	public function get( $key ) {
		return $this->cache[$key];
	}
	public function setField( $key, $field, $value ) {
		$this->cache[$key][$field] = $value;
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

class LogEventsList  {
	public static function showLogExtract( &$out ) {
		HardcodedSimpleTaint::yesArgReturnsEscaped( getEscaped() );
	}
}


class OutputPage {
	public function addHTML( $text ) {
	}
}


class HardcodedSimpleTaint {
	public static function yesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
	public static function escapesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
}
