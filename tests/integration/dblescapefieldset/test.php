<?php

class Xml {
	public static function fieldset( $legend, $content ) {
		$s = htmlspecialchars( $legend ) . $content;
		return $s;
	}
}

$content2 = htmlspecialchars( 'foobar' );
echo Xml::fieldset(
	'foo',
	$content2
);
