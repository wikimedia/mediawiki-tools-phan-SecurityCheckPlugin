<?php

class MyLinker {
	/**
	 * @param-taint $target none
	 * @param-taint $html exec_html
	 */
	public static function doLink( $target, $html ) {
		// placeholder
	}
}

$safe = MyLinker::doLink( $_GET['dontcare'], 'safeString' );
echo $safe;
$unsafe = MyLinker::doLink( 'xx', $_GET['baz'] );
echo $unsafe; // Safe

$unsafeContent = $_GET['foo'] . 'safestring';
echo MyLinker::doLink( 'xx', $unsafeContent ); // Unsafe call, safe echo

$safeContent = 'safe';
echo MyLinker::doLink( $_GET['bar'], htmlspecialchars( $safeContent ) ); // Safe
