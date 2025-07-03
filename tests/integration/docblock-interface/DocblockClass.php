<?php

class DocblockClass implements DocblockInterface {
	public function escapeHTML( $t ) {
		return $t;
	}

	public function getUnsafeHTML() {
		return '<blink>evil</blink>';
	}

	public function getUserInput() {
		return '!user';
	}

	public function doQuery( $query ) {
		return true;
	}

	public function wfShellExec2( $line ) {
		return 0;
	}

	public function getSomeSQL() {
		return 'SELECT 12;';
	}

	public function safeOutput( $foo ) {
		echo $foo;
	}

	public function getSafeString() {
		return $_GET['foo'];
	}

	public function invalidTaint() {
		return '<foo>';
	}

	public function multiTaint( $t ) {
		return null;
	}

	public function passbyRef( $foo, &$bar ) {
		return "f";
	}
}
