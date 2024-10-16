<?php

class ClassWithCallbacks {
	/** @var array No specific type here, so phan doesn't know what the elements could be */
	private array $prop = [];

	public function setProp() {
		$this->prop = $GLOBALS['foobar']; // Still no union type
	}

	public function runTest() {
		if ( $this->prop['foo'] instanceof stdClass ) {// This makes phan infer that the values in $this->prop might be stdClass instances
			return 42;
		} else {
			// Here phan has no idea of what's inside $this->prop, but it knows that stdClass is an option (even if not
			// in this branch). Therefore, when using ContextNode::getFunctionFromNode(), it may think we're calling the
			// stdClass object itself, and complain about its lack of an `__invoke` method. We avoid that by copying
			// what ClosureReturnTypeOverridePlugin does, so we don't emit any new issues.
			return call_user_func_array( $this->prop['foo'], [ 42 ] );
		}
	}
}