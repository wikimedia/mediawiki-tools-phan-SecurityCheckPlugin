<?php

class NestedCalls {
	public function first() {
		$this->second();
	}

	public function second() {
		echo $this->third();
	}

	public function third() {
		return self::fourth( $_GET['foo'] );
	}

	public static function fourth( $text ) {
		return HardcodedSimpleTaint::escapesArgReturnsEscaped( $text );
	}
}

class NestedCalls2 {
	public function a() {
		$this->b();
	}

	private function b() {
		$this->c();
	}

	private function c() {
		echo $this->d( $_GET['baz'] );
	}

	public function d( $arg ) {
		return htmlspecialchars( $arg );
	}
}
