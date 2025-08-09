<?php

use MediaWiki\Message\Message;

class MsgString {

	/**
	 * @param Message|string $f
	 */
	public static function escapeIfNotMessage( $f ) {
		if ( $f instanceof Message ) {
			$f = 'literal';
		}
		echo htmlspecialchars( $f );
	}

	/**
	 * @param Message|string $f
	 */
	public static function echoIfNotMessage( $f ) {
		if ( $f instanceof Message ) {
			echo 'literal';
		} else {
			echo $f;
		}
	}

	public static function alwaysEscape( $f ) {
		echo htmlspecialchars( $f );
	}

	// Same as escapeIfNotMessage() but no docblock so should still warn.
	public static function escapeIfNotMessageWithoutDocblock( $f ) {
		if ( $f instanceof Message ) {
			$f = 'literal';
		}
		echo htmlspecialchars( $f );
	}
}

// Good
$msg = new Message;
MsgString::escapeIfNotMessage( $msg ); // This must NOT emit a DoubleEscaped warning
MsgString::escapeIfNotMessageWithoutDocblock( $msg ); // This should give a false positive warning.
MsgString::echoIfNotMessage( $_GET['a'] ); // XSS
MsgString::alwaysEscape( $msg ); // Genuine DoubleEscaped warning

MsgString::notExist( $msg ); // This should not give warning
