<?php

// Note that it's vital that the param to User::newFromId and the local variable inside TestUser::execute
// have the same name.

class TestUser {
	public function execute() {
		$sameName = $_GET['baz'];
		if ( rand() ) {
			User::newFromId( $sameName );
		}
		$this->output( $sameName ); // Obviously unsafe
		User::newFromId( $_GET['foo'] ); // This is safe!
	}
	public function output( $msg ) {
		echo $msg;
	}
}
