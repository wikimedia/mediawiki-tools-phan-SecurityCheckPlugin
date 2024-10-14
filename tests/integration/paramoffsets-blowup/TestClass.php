<?php

// See comment in the other file for explanations.
// Note that this class needs to be analyzed after the other one.

class TestClass {
	private string $prop = '';

	public function execute() {
		$obj = new self();
		$obj->setProp( 'foo' );

		( new ParamCollector() )->params( $obj->prop );
	}

	public static function newInstance( string $propVal ): self {
		$ret = new self();
		$ret->prop = $propVal;
		return $ret;
	}

	public function getInstance1(): self {
		return self::newInstance( $this->prop );
	}

	public function getInstance2(): self {
		return self::newInstance( $this->prop );
	}

	public function getInstance3(): self {
		return self::newInstance( $this->prop );
	}

	public function getInstance4(): self {
		return self::newInstance( $this->prop );
	}

	public function getInstance5(): self {
		return self::newInstance( $this->prop );
	}

	private function setProp( string $text ) {
		$new = self::newInstance( 'Safe' );
		$this->prop = $text . $new->prop;
	}

	public function getInstanceParam( $par ) {
		return self::newInstance( $par );
	}
}
