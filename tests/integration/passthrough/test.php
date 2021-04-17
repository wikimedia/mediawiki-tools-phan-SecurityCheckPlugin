<?php

function passthrough( string $str ) : string {
	return $str;
}
echo passthrough( $_GET['a'] ); // Unsafe

function noPassthrough( string $x ) : string {
	return htmlspecialchars( 'foo' );
}
echo noPassthrough( $_GET['a'] ); // Safe

function partialPassthrough( string $x ) : string {
	return htmlspecialchars( $x );
}
echo partialPassthrough( $_GET['a'] ); // Safe
htmlspecialchars( partialPassthrough( $_GET['a'] ) ); // DoubleEscaped
require partialPassthrough( $_GET['a'] ); // TODO: Ideally PT, but htmlspecialchars clears the PRESERVE bit

function indirectPassthrough( string $x ) : string {
	$y = $x;
	return $y;
}
echo indirectPassthrough( $_GET['a'] ); // Unsafe

function preservePassthrough( array $x ) : string {
	$y = array_merge( $x, [ 'foo', 'bar' ] ); // Should be in caused-by
	$z = implode( '; ', $y ); // Should be in caused-by
	return substr( $z, 2, 15 );
}
echo preservePassthrough( $_GET['a'] ); // TODO Unsafe

function taintAndPassthrough( string $x ) : string {
	return $x . $_GET['a'];
}
echo taintAndPassthrough( $_GET['a'] ); // Unsafe
echo taintAndPassthrough( 'foo' ); // Unsafe

class Html {
	static function element( $x, $y, $z ) {
		return $y . $z; // Shouldn't be seen as a passthrough because Html::element is annotated
	}
}
echo Html::element( 'foo', $_GET['A'] ); // Safe

$x = htmlspecialchars( $_GET['a'] );
echo $x; // Safe
shell_exec( $x ); // Unsafe
