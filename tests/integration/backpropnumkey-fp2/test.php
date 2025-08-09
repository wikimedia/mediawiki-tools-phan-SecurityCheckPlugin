<?php

class BackpropNumkeyFP2Class1 {
	protected function test() {
		( new BackpropNumkeyFP2ValueObject( 'foo' ) )->save();
	}
}
class BackpropNumkeyFP2Class2 {
	public function execute() {
		new BackpropNumkeyFP2ValueObject( $_GET['a'] );
	}
}


class BackpropNumkeyFP2ValueObject {
	public $arrayProp;

	public function save() {
		execNumkey( $this->arrayProp );// Should not backpropagate NUMKEY
	}

	public function __construct( $safeVal ) {
		$this->arrayProp = [ 'string' => $safeVal ];
	}
}
