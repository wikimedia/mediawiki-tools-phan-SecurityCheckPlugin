<?php
namespace Wikimedia\Rdbms;

interface IDatabase {
	public function query( $sql );
	public function addIdentifierQuotes( $arg );
}

class Database implements IDatabase {
	public function query( $sql ) {
		// do some stuff
		return [ (object)[ 'some_field' => 'some value' ] ];
	}

	public function addIdentifierQuotes( $arg ) {
		return 'placeholder';
	}
}
