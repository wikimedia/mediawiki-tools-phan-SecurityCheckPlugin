<?php

use Wikimedia\Rdbms\InsertQueryBuilder;

/*
 * Regression test for a scenario when a setter was tainting a whole property even if the setter was never called.
 * Note that a few seemingly irrelevant details are actually important here:
 *  - triggerMethod() must be a method. A normal function or code in the global scope won't work.
 *  - The classes should be defined in this exact order.
 *  - The setAttribs method must be defined, even if it's unused.
 */

class IndirectTrigger {
	private function triggerMethod() {
		DirectTrigger::doTrigger();
	}
}

class MainClass {
	public array $mAttribs = [];

	public function setAttribs( array $attribs ) {
		$this->mAttribs = $attribs;
	}

	public function save() {
		( new InsertQueryBuilder() )->row( $this->mAttribs );// This is safe because nobody sets unsafe keys in mAttribs.
	}

	public static function newWithData( string $datum ) {
		$rc = new self;
		$rc->mAttribs = [
			'some safe key' => $datum
		];
	}

}

class DirectTrigger {
	public static function doTrigger() {
		MainClass::newWithData( $_GET['a'] ); // Safe!
	}
}