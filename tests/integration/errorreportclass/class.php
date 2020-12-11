<?php

class Foo {
	private $fooMember;

	public function setFooMember( $valFoo ) {
		$this->fooMember = $valFoo;
	}

	public function getFooMember() {
		return $this->fooMember;
	}
}
