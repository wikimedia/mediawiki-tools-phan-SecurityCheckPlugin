<?php

class RecursiveSetter {
	public $foo;
	public function setFoo( $x ) {
		$this->foo = $x;
		if ( rand() ) {
			$this->setFoo( htmlspecialchars( $x ) );
		}
	}
}

$r = new RecursiveSetter();
$r->setFoo( htmlspecialchars( $_GET['a'] ) );
