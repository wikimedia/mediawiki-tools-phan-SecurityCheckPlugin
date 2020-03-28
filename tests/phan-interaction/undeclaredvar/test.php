<?php
/*
 * For T248742
 */

class Foo {
	public $prop = 2;
	public static function foobar() {
		return new self;
	}

	public function baz() {
		$ret = self::foobar()->prop; // This would have emitted "Variable $self is undeclared"
		return $ret; // And "Variable $ret is undeclared" here
	}
}
