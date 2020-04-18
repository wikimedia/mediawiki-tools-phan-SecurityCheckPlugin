<?php

class EditPage {
	protected function getLastDelete() {
		$data = new stdClass();
		// This assignment used to be conflated with the one in User
		$data->user_name = htmlspecialchars( 'x' );
	}
}


class User {
	public $mName;

	public function loadDefaults( $name = false ) {
		$this->mName = $name;
	}

	protected function loadFromRow( stdClass $row ) {
		// Here it would pick up ESCAPED_TAINT
		$this->mName = $row->user_name;
	}

	public function getName() {
		$this->loadDefaults( 'x' );
		return $this->mName;
	}
}

function func1() {
	$u = new User;
	htmlspecialchars( $u->getName() ); // No DoubleEscaped
}
function func2( User $user ) {
	htmlspecialchars( $user->getName() ); // No DoubleEscaped
}
