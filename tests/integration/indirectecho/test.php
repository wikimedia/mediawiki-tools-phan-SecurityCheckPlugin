<?php
class IndirectEcho {

	private $hold = '';

	public function appendHold( $param ) {
		$this->hold .= $param;
	}

	public function echoHold() {
		echo $this->hold;
	}
}
