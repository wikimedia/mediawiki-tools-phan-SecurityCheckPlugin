<?php
class Foo {
	private static function getEvil() {
		return $_GET['baz'];
	}
}

echo Foo::getEvil();
