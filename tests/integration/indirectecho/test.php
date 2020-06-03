<?php
class Foo {

	private $hold = '';

	public function appendHold( $param ) {
		$this->hold .= $param; // TODO: This line should be in caused-by
	}

	public function echoHold() {
		echo $this->hold;
	}
}
