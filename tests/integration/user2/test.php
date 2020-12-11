<?php

class Foo {
	public function output( $msg ) {
		echo $msg;
	}

	public function execute() {
		$user = User::newFromName( $_GET['baz'] );
		$this->output( $user->getName() ); // This is unsafe
		User::newFromName( $_GET['baz'] ); // And we consider this one unsafe as well.
	}
}
