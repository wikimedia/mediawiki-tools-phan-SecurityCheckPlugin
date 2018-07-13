<?php

/**
 * Tests a bug where if a parameter is both marked exec
 * and (normal) taint, the normal taint gets back propagated
 * and then the function fixes itself. File is based on
 * structure of DummyLinker in MediaWiki, as that was where
 * bug was first found.
 */

class Dummy {
	public function someFunc( $foo ) {
		return StaticDummy::someFunc( $foo );
	}
}

class StaticDummy {
	public static function someFunc( $foo ) {
		return self::bar( $foo );
	}

	/**
	 * @param string $foo
	 * @param-taint $foo2 exec_html,tainted
	 * @return string
	 * @return-taint escaped
	 */
	public static function bar( $foo2 ) {
		return "<a>$foo2</a>";
	}
}

echo StaticDummy::someFunc( "foo" );
echo StaticDummy::someFunc( $_GET['evil'] );
