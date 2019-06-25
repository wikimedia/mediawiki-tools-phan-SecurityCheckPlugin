<?php

class User {
	public $name;
	public static function newFromName( $name ) {
		$u = new User;
		$u->name = $name;
		return $u;
	}
	public function getName() {
		return $this->name;
	}
}
