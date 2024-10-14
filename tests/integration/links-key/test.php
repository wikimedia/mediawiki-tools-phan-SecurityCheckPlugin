<?php

class LinksOnKeyOfVal {
	public static function echoKeyOfVal( array $par ) {
		foreach ( $par as $value ) {
			foreach ( $value as $k => $v ) {
				echo $k;
			}
		}
	}
}

'@taint-check-debug-method-first-arg LinksOnKeyOfVal::echoKeyOfVal';

LinksOnKeyOfVal::echoKeyOfVal( [ $_GET['a'] ] ); // XSS, caused by 7, 6, 5