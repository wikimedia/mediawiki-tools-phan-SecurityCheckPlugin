<?php

class NestedCalls {
	public function execute() {
		$this->show();
	}

	public function show() {
		echo $this->foo();
	}

	public function foo() {
		return self::bar( $_GET['foo'] );
	}

	public static function bar( $text ) {
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
