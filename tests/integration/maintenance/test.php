<?php
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
