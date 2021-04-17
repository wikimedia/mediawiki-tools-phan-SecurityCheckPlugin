<?php

class TestReturnNoKnownObject {
	function testFoo( string $arg ) {
		// Ensure we don't crash if a returned object cannot be determined statically.
		return $this->$arg;
	}
}
