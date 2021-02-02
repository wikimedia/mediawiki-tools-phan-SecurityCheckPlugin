<?php

class MyObj {
	public function echoArg( $x ) {
		echo $x;
	}
	public function getUnsafe() {
		return $_GET['unsafe'];
	}
	public function getSafe() {
		return 'safe';
	}
}

function maybeGetObj() : ?MyObj {
	return rand() ? new MyObj : null;
}

$obj = maybeGetObj();
$obj?->echoArg( $_GET['unsafe'] ); // Unsafe
$obj?->echoArg( 'safe' ); // Safe
echo $obj?->getUnsafe(); // Unsafe
echo $obj?->getSafe(); // Safe
