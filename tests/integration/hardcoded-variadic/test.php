<?php

class HardcodedVariadicExec {
	public function doTest( ...$params ): void {
	}
}

( new HardcodedVariadicExec )->doTest( $_GET['baz'] ); // XSS
( new HardcodedVariadicExec )->doTest( 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, $_GET['baz'] ); // XSS

function callsHardcodedAfterLiterals( $par ) {
	( new HardcodedVariadicExec )->doTest( 'a', 'b', 'c', $par );
}
callsHardcodedAfterLiterals( $_GET['unsafe'] ); // XSS

function callsHardcodedVariadic( ...$par ) {
	( new HardcodedVariadicExec )->doTest( $par );
}
callsHardcodedVariadic( 'a', 'b', 'c', $_GET['unsafe'] ); // XSS
