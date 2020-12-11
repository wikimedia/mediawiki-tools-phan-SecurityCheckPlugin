<?php

// NOTE: The test directory should not be called "maintenance" because that's tested in the test "maintenance";
// Here we check maintenance subclasses NOT in the maintenance directory.

abstract class Maintenance {
	abstract public function execute();
}

class MyCLIScript extends Maintenance {
	public function execute() {
		echo $argv[1];
		// However this should not be false positive
		eval( $argv[1] );
	}
}
