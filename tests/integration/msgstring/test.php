<?php
class Foo {

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
Foo::a( 'safe' );
Foo::giveWarning( 'safe' );
Foo::b( 'safe' );
$msg = new Message;
Foo::a( $msg ); // safe. This is what test is primarily about.
Foo::giveWarning( $msg ); // This should give a false positive warning.
Foo::b( $msg );
Foo::evil1( $msg ); // This is unsafe, but should trigger at line 30
Foo::justEcho( $msg ); // unsafe double escape.

Foo::a( $_GET['d'] ); // safe
Foo::giveWarning( $_GET['d'] ); // safe
Foo::b( $_GET['d'] ); // unsafe
Foo::notExist( $msg ); // This should not give warning
