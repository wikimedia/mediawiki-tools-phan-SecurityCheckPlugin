<?php

class Linker {
	public static function linkKnown(
		$target, $html = null, $customAttribs = [],
		$query = [], $options = [ 'known' ]
	) {
		// placeholder
	}
}

$safe = Linker::linkKnown( $_GET['dontcare'], 'safeString' );
echo $safe;
$unsafe = Linker::linkKnown( 'xx', $_GET['baz'] );
echo $unsafe;

$unsafeContent = $_GET['foo'] . 'safestring';
echo Linker::linkKnown( 'xx', $unsafeContent ); // Unsafe

$safeContent = 'safe';
echo Linker::linkKnown( $_GET['bar'], htmlspecialchars( $safeContent ) ); // Safe