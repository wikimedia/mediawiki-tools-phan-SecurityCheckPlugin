<?php

class CallWithEvil3 {
	public static function output( $arg1, $arg2 ) {
		echo $arg1;
	}
}

$a = $_GET['foo'];
$c = "Some safe string";

CallWithEvil3::output( $_GET['bar'], $a );
