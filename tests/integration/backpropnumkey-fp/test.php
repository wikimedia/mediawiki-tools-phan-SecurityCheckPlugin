<?php

use Wikimedia\Rdbms\Database;

class StatusValue {
	/** @var mixed */
	public $value;

	public function getValue() {
		return $this->value;
	}

	public function setResult( $value ) {
		$this->value = $value;
	}
	public static function wrap( StatusValue $sv ) {
		$result = new static();
		$result->value =& $sv->value;

		return $result;
	}
}

class UploadStash {
	protected $fileMetadata = [];

	public function stashFile( $path, $sourceType = null ) {
		$storeStatus = new \StatusValue();
		$stashPath = $storeStatus->value;
		$dbw = new Database;
		$this->fileMetadata['foo'] = [
			'us_path' => $stashPath,
		];
		$dbw->insert( 'uploadstash', $this->fileMetadata['foo'] ); // NOT an SQLi, and it should NOT backpropagate numkey on fileMetadata
	}

}

class RevisionStore {
	private function getSlotRowsForBatch() {
		$result = new \StatusValue();
		$result->setResult( $_GET['a'] ); // NOT an SQLi caused by lines 29-31-34
		return $result;
	}
}


class UserrightsPage {

	public function fetchUser() {
		$name = UserRightsProxy::whoIs();
		$user = UserRightsProxy::newFromName( $name );
		$status = new StatusValue();
		$status->setResult( $user ); // NOT an SQLi caused by lines 29-31-34
	}

}

class UserRightsProxy {
	private $name;

	private function __construct( string $name ) {
		$this->name = $name;
	}

	public static function whoIs() {
		return self::newFromName( 'x' )->name;
	}

	public static function newFromName( $name ) {
		return self::newFromLookup( 'user_name', $name );
	}

	private static function newFromLookup( $field, $value ) {
		self::getDB()->selectRow( 'user', '', [ $field => $value ] ); // This should NOT backpropagate numkey on $value
		return new UserRightsProxy( $_GET['a'] );
	}

	public static function getDB() : Database {
	}

}
