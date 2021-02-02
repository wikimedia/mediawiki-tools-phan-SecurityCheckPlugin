<?php

class MyObj {
	public $safe;
	public $unsafe;

	public function __construct() {
		$this->safe = 'safe';
		$this->unsafe = $_GET['x'];
	}
}

function maybeGetObj() : ?MyObj {
	return rand() ? new MyObj : null;
}

$obj = maybeGetObj();
echo $obj?->safe; // Safe
echo $obj?->unsafe; // Unsafe
