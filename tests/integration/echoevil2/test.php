<?php
class EchoEvil2 {

	private function doEvil() {
		echo $_GET['baz'];
	}

	private function notEvil() {
		echo "something";
	}

}
