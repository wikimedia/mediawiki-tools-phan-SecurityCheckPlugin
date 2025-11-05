<?php

namespace TestForward;

/**
 * Tests a bug where if a parameter is both marked exec
 * and (normal) taint, the normal taint gets back propagated
 * and then the function fixes itself. File is based on
 * structure of DummyLinker in MediaWiki, as that was where
 * bug was first found.
 */

class TestNonStatic {
	public function testNonStaticFunc( $foo ) {
		return TestStatic::testStaticFuncOuter( $foo );
	}
}

class TestStatic {
	public static function testStaticFuncOuter( $foo ) {
		return self::testStaticFunc( $foo );
	}

	/**
	 * @param string $foo
	 * @param-taint $foo2 exec_html,tainted
	 * @return string
	 * @return-taint escaped
	 */
	public static function testStaticFunc( $foo2 ) {
		return "<a>$foo2</a>";
	}
}

echo TestStatic::testStaticFuncOuter( "foo" );
echo TestStatic::testStaticFuncOuter( $_GET['evil'] ); // TODO The echo (not just the call) is unsafe, but we don't handle nested PRESERVE yet.
echo TestStatic::testStaticFunc( $_GET['evil'] ); // Unsafe, caused by annotations
