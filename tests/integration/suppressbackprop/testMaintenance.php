<?php
class Maintenance {
}

class SuppressBackpropMaintenance extends Maintenance {

	public $msg;

	public function __construct( $msg ) {
		$this->msg = $msg;
	}

	public function out() {
		echo $this->msg;
	}

}

$b = $_GET['foo'];
$a = new SuppressBackpropMaintenance( $b );
$a->out();
