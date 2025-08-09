<?php

use Wikimedia\Rdbms\Database;

class BackpropNumkeyFP3 {
	private $someProp;

	public static function newInstance( string $x ): self {
		$ret = new self;
		$ret->someProp = $x;
		return $ret;
	}

	public function getStringKeyArray(): array {
		return [ 'string' => $this->someProp ];
	}

	private function doTest() {
		execNumkey( $this->getStringKeyArray() ); // The returned array doesn't have num keys, so this is safe
	}
}

BackpropNumkeyFP3::newInstance( $_GET['foo'] ); // Safe.
