<?php namespace PassByRefOffset;

/*
 * Test for passing array elements by ref. Phan doesn't use PassByReferenceVariable here, so we cannot properly handle them.
 * This test mainly serves to ensure that the plugin won't crash.
 */

function unsafe( &$arg1 ) {
	$arg1 = $_GET['x'];
}
function safe( &$arg2 ) {
	$arg2 = 'safe';
}

class TestMethod {
	public $baz;
	function main() {
		unsafe( $this->baz['foo'] );
		echo $this->baz['foo']; // TODO: Ideally unsafe
	}
}

$foo = $_GET;
safe( $foo['baz'] );
echo $foo['baz']; // TODO: Ideally safe.
