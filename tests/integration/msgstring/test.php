<?php

use MediaWiki\Message\Message;

class MsgString {

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
MsgString::a( 'safe' );
MsgString::giveWarning( 'safe' );
MsgString::b( 'safe' );
$msg = new Message;
MsgString::a( $msg ); // safe. This is what test is primarily about.
MsgString::giveWarning( $msg ); // This should give a false positive warning.
MsgString::b( $msg );
MsgString::evil1( $msg ); // This is unsafe, but should trigger at line 30
MsgString::justEcho( $msg ); // unsafe double escape.

MsgString::a( $_GET['d'] ); // safe
MsgString::giveWarning( $_GET['d'] ); // safe
MsgString::b( $_GET['d'] ); // unsafe
MsgString::notExist( $msg ); // This should not give warning
