<?php

class SelfTaint {

	public $msg;

	public function __construct( $msg ) {
		$this->msg = $msg;
	}

	public function out() {
		echo $this->msg;
	}

	public function life() {
		// We should not get a warning here
		$noop = $this->msg;
		return 42;
	}

}

$b = $_GET['foo'];
$a = new SelfTaint( $b );
$a->out();
