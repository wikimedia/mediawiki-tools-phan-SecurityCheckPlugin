<?php

class Foo {

	/** @var string $myProp */
	public $myProp = '';


	public function setMyProp( $p ) {
		$this->myProp = $p;
	}

	public function echoMyProp() {
		echo $this->myProp;
	}

}
