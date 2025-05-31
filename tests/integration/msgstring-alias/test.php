<?php // TODO: Drop this test when the \Message alias is dropped from MW core.
class MsgStringAlias {

	/**
	 * @param Message|string $f
	 */
	public static function a( $f ) {
		if ( $f instanceof Message ) {
			$f = $f->text();
		}
		echo htmlspecialchars( $f );
	}

	/**
	 * @param Message|string $f
	 */
	public static function b( $f ) {
		if ( $f instanceof Message ) {
			echo $f->parse();
		} else {
			echo $f;
		}
	}

	/**
	 * @param Message|string $f
	 */
	public static function evil1( $f ) {
		if ( $f instanceof Message ) {
			echo $f->text();
		} else {
			echo htmlspecialchars( $f );
		}
	}

	public static function justEcho( $f ) {
		echo htmlspecialchars( $f );
	}

	// Same as a() but no docblock so should still warn.
	public static function giveWarning( $f ) {
		if ( $f instanceof Message ) {
			$f = $f->text();
		}
		echo htmlspecialchars( $f );
	}
}

// Good
MsgStringAlias::a( 'safe' );
MsgStringAlias::giveWarning( 'safe' );
MsgStringAlias::b( 'safe' );
$msg = new Message;
MsgStringAlias::a( $msg ); // safe. This is what test is primarily about.
MsgStringAlias::giveWarning( $msg ); // This should give a false positive warning.
MsgStringAlias::b( $msg );
MsgStringAlias::evil1( $msg ); // This is unsafe, but should trigger at line 30
MsgStringAlias::justEcho( $msg ); // unsafe double escape.

MsgStringAlias::a( $_GET['d'] ); // safe
MsgStringAlias::giveWarning( $_GET['d'] ); // safe
MsgStringAlias::b( $_GET['d'] ); // unsafe
MsgStringAlias::notExist( $msg ); // This should not give warning
