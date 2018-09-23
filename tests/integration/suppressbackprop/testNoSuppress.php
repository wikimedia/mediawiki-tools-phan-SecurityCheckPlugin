<?php

class Foo2 {

	public $msg;

	public function __construct( $msg ) {
		$this->msg = $msg;
	}

	public function out() {
		echo $this->msg;
	}

	public function exec() {
		$command2 = $this->msg;
		return `$command2`;
	}

	public function includeMsg() {
		return include $this->msg;
	}

	/**
	 * @param-taint $param exec_custom1
	 */
	public function customEvil( $param ) {
		return 42;
	}

	public function doCustom() {
		return $this->customEvil( $this->msg );
	}

}

$c = $_GET['foo'];
$d = new Foo2( $c );
$d->out();
