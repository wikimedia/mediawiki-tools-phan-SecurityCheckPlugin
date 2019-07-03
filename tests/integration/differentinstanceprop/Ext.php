<?php

class Baz {
	public function foo() {
		MainClass::fromId( $_GET['baz'] );
	}
}
