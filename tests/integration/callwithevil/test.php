<?php
class CallWithEvil {
	public static function output( $arg1, $arg2 ) {
		echo $arg1;
	}
}

$a = $_GET['foo'];
$c = "Some safe string";

CallWithEvil::output( $a, 'foo' );
