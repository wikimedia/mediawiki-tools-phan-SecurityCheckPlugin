<?php

namespace TestProp2;

class TestProp2 {
	public $f = '';

	/** @var SinkGetter */
	private $sinkGetter;

	public function __construct() {
		$this->sinkGetter = new SinkGetter;
	}

	public function main() {
		$sink = $this->sinkGetter->getSink();
		$sink->execHTML( $this->f );
	}
}

class SinkGetter {
	/**
	 * @return SinkClass
	 */
	public function getSink() {
		return new SinkClass;
	}
}

class SinkClass {
	public function execHTML( $html ) {
	}
}

$test = new TestProp2;
$test->main();
