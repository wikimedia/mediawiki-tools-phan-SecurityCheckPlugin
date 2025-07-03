<?php

class TestProp2 {
	public $f = '';

	/** @var Context */
	private $context;

	public function __construct() {
		$this->context = new Context;
	}

	public function bar() {
		$out = $this->context->getOutput();
		$out->addHTML( $this->f );
	}
}

class Context {
	/**
	 * @return OutputPage
	 */
	public function getOutput() {
		return new OutputPage;
	}
}

class OutputPage {
	public function addHTML( $html ) {
	}
}

$test = new TestProp2;
$test->bar();
