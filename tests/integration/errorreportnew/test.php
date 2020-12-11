<?php

class Foo {
	public function __construct( $foo ) {
		echo $foo;
	}
}

$evil = $_GET['evil'];
new Foo( $evil );
