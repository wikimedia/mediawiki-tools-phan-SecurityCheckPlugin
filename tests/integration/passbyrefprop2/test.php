<?php

class PassByRef {
	public $myProp1;
	public $myProp2;

	function passByRefUnsafe( &$arg ) {
		$arg = $_GET['x'];
	}

	function passByRefSafe( &$arg ) {
		$arg = htmlspecialchars( $arg );
	}

	public function testUnsafe1() {
		// NOTE: While this IS unsafe due to line 26, taint-check doesn't know yet. This is unrelated
		// to passbyref, instead it's because the plugin still doesn't know what will set myProp2.
		echo $this->myProp2;
	}
	function evilProp1() {
		$this->passByRefUnsafe( $this->myProp1 );
	}


	function evilProp2() {
		$this->passByRefUnsafe( $this->myProp2 );
	}
	function safeProp1() {
		$this->passByRefSafe( $this->myProp1 );
	}
	function testUnsafe2() {
		echo $this->myProp1; // Unsafe, not caused by line 29
	}
}
