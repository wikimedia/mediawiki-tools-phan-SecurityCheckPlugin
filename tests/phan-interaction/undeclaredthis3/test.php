<?php
/*
 * For T249619
 */



class ActorMigration {
	function getInsertValues( \MediaWiki\User\UserIdentity $user ) {
		User::newFromAnyId( $user->getId() );
	}

}

class User implements UserIdentity {
	public $mId;

	public function __toString() {
		return (string)$this->getName();
	}

	public function load( $flags = 1 ) {
		$this->loadFromId();
	}

	public function loadFromId( $flags = 1 ) {
		$this->loadFromDatabase();
	}

	public static function newFromAnyId( $userId ) : User {
	}

	public function loadFromDatabase() {
		$this->loadFromRow();
	}

	protected function loadFromRow() {
		$this->loadOptions();
	}

	public function getId() {
		return $this->mId; // This test is about avoiding "Variable $this is undeclared" here
	}

	public function getName() {
		$this->load();
	}

	protected function loadOptions() {
		htmlspecialchars($this->getId());
	}
}
