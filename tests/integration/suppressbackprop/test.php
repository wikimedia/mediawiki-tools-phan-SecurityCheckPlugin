<?php

class Foo {

	public $msg;

	public function __construct( $msg ) {
		$this->msg = $msg;
	}

	/**
	 * @suppress SecurityCheck-XSS
	 */
	public function out() {
		echo $this->msg;
	}

	/**
	 * @suppress SecurityCheck-ShellInjection
	 */
	public function exec() {
		$command2 = $this->msg;
		return `$command2`;
	}

	/**
	 * @suppress SecurityCheck-PathTraversal
	 */
	public function includeMsg() {
		return include $this->msg;
	}

	/**
	 * @param-taint $param exec_custom1
	 */
	public function customEvil( $param ) {
		return 42;
	}

	/**
	 * @suppress SecurityCheck-CUSTOM1
	 */
	public function doCustom() {
		return $this->customEvil( $this->msg );
	}

}

$b = $_GET['foo'];
$a = new Foo( $b );
$a->out();
