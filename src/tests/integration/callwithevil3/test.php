<?php
class Foo {
	private static function output( $arg1, $arg2 ) {
		echo $arg1;
	}
}

$a = $_GET['foo'];
$c = "Some safe string";

Foo::output( $_GET['bar'], $a );

