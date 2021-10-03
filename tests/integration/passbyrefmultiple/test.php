<?php

class PassByRefMultiple {
	function setToUnsafe( &$arg1 ) {
		$arg1 = $_GET['x'];
	}

	function appendUnsafe( &$arg2 ) {
		$arg2 .= $_GET['x'];
	}

	function setToSafe( &$arg3 ) {
		$arg3 = 'foo';
	}

	function escapeArg( &$arg4 ) {
		$arg4 = htmlspecialchars( $arg4 );
	}

	function testSafe1() {
		$var1 = 'safe';
		$this->setToUnsafe( $var1 );
		$this->escapeArg( $var1 );
		echo $var1;
	}

	function testSafe2() {
		$var2 = $_GET;
		$this->escapeArg( $var2 );
		$this->setToUnsafe( $var2 );
		$this->setToSafe( $var2 );
		echo $var2;
	}

	function testDoubleEsc1() {
		$var3 = null;
		$this->setToUnsafe( $var3 );
		$this->escapeArg( $var3 );
		$this->escapeArg( $var3 );
	}

	function testSafe3() {
		$var4 = 'safe';
		$this->escapeArg( $var4 );
		$this->setToSafe( $var4 );
		$this->escapeArg( $var4 );
		echo $var4;
	}

	function testUnsafe1() {
		$var5 = '';
		$this->setToUnsafe( $var5 );
		$this->escapeArg( $var5 );
		$this->appendUnsafe( $var5 );
		echo $var5; // Unsafe, not caused by line 53
	}

	function testSafe4() {
		$var6 = $_GET['u'];
		$this->setToSafe( $var6 );
		$this->appendUnsafe( $var6 );
		$this->escapeArg( $var6 );
		echo $var6;
	}
}
