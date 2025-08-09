<?php // TODO: Drop this test when the \Message alias is dropped from MW core.
class MsgStringAlias {

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
MsgStringAlias::escapeIfNotMessage( $msg ); // This must NOT emit a DoubleEscaped warning
MsgStringAlias::escapeIfNotMessageWithoutDocblock( $msg ); // This should give a false positive warning.
MsgStringAlias::echoIfNotMessage( $_GET['a'] ); // XSS
MsgStringAlias::alwaysEscape( $msg ); // Genuine DoubleEscaped warning

MsgStringAlias::notExist( $msg ); // This should not give warning
