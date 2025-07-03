<?php
class CallWithEvil2 {
	public static function output( $arg1, $arg2 ) {
		echo $arg1;
	}
}

$a = $_GET['foo'];
$c = "Some safe string";

CallWithEvil2::output( $c, $a );
