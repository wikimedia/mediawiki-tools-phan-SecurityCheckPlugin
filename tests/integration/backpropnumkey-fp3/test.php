<?php

use Wikimedia\Rdbms\Database;

class MyTestClass {
	private $someProp;

	public static function newInstance( string $x ): self {
		$ret = new self;
		$ret->someProp = $x;
		return $ret;
	}

	public function getDBCond(): array {
		return [ 'page_title' => $this->someProp ];
	}

	private function doQuery() {
		$dbw = new Database();
		$dbw->selectRow(
			'mytable',
			'*',
			$this->getDBCond() // The returned array doesn't have num keys, so this is safe
		);
	}
}

MyTestClass::newInstance( $_GET['foo'] ); // Safe.
