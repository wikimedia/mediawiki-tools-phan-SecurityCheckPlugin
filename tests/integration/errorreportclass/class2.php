<?php

class Baz {
	private $bazMember;

	public function __construct( $valBaz ) {
		$this->bazMember = new Foo;
		$this->bazMember->setFooMember( $valBaz );
	}

	public function echoVal() {
		echo $this->bazMember->getFooMember();
	}
}

$b = new Baz( $_GET['evil'] );
