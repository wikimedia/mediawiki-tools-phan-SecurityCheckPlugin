<?php
class EchoEvilFunc {
	public static function getEvil() {
		return $_GET['baz'];
	}
}

echo EchoEvilFunc::getEvil();
