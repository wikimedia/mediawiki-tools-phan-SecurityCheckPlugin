<?php

class PassByRef {
	function passByRefUnsafe( &$arg ) {
		$arg = $_GET['x'];
	}

	function testUnsafe1() {
		$var1 = 'foo';
		$this->passByRefUnsafe( $var1 );
		echo $var1;
	}

	function passByRefSafe( &$arg ) {
		$arg = htmlspecialchars( $arg );
	}

	function testSafe1() {
		$var2 = $_GET['x'];
		$this->passByRefSafe( $var2 );
		echo $var2;
	}

	function testSafe2() {
		$var2 = $_GET['x'];
		$this->passByRefSafeAfterExec( $var2 );
		echo $var2;
	}

	function passByRefSafeAfterExec( &$arg ) {
		$arg = htmlspecialchars( $arg );
	}

	function passByRefSafeRhs( &$arg ) {
		$arg = htmlspecialchars( 'x' );
	}

	function testSafe3() {
		$var2 = $_GET['x'];
		$this->passByRefSafeRhs( $var2 );
		echo $var2;
	}

	function passByRefRandomEvil( &$arg ) {
		if ( rand() ) {
			$arg = $_GET['x'];
		}
	}

	function testUnsafe2() {
		$var3 = 'foo';
		$this->passByRefRandomEvil( $var3 );
		echo $var3;
	}

	function passByRefRandomMixed( &$arg ) {
		if ( rand() ) {
			$arg = $_GET['x'];
		} else {
			$arg = htmlspecialchars( $arg );
		}
	}

	function testBoth1() {
		$var4 = $_GET['x'];
		$this->passByRefRandomMixed( $var4 );
		echo $var4;
	}

	function testBoth2() {
		$var5 = htmlspecialchars( $_GET['z'] );
		$this->passByRefRandomMixed( $var5 );
		echo $var5;
	}

	function testBoth3() {
		$var6 = 'safe';
		$this->passByRefRandomMixed( $var6 );
		echo $var6;
	}
}
