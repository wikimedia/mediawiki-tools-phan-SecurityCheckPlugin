<?php
class Maintenance {
}

class Foo3 extends Maintenance {

	public $msg;

	public function __construct( $msg ) {
		$this->msg = $msg;
	}

	public function out() {
		echo $this->msg;
	}

}

$b = $_GET['foo'];
$a = new Foo3( $b );
$a->out();
