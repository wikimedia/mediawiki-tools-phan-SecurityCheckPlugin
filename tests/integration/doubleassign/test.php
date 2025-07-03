<?php

class DoubleAssign {

	public function doStuff( $s ) {
		return $s;
	}

	public function doStuff2( $s ) {
		$foo = $s;
		$foo .= 'Stuff';
		$foo = htmlspecialchars( $foo );
		return $foo;
	}

	public function doStuff3( $s ) {
		$foo = $s;
		$foo .= 'Stuff';
		if ( $s === 'something' ) {
			$foo = htmlspecialchars( $foo );
		}
		return $foo;
	}
}

$a = new DoubleAssign;
echo $a->doStuff( $_GET['evil'] ); // Unsafe
echo $a->doStuff2( $_GET['evil'] ); // Safe
echo $a->doStuff3( $_GET['evil'] ); // Unsafe

echo $a->doStuff3( "safe" ); // Safe
echo $a->doStuff2( "safe" ); // Safe
echo $a->doStuff( "safe" ); // Safe
