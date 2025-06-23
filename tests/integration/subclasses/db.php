<?php
namespace Wikimedia\Rdbms;

interface IDatabase {
	public function query( $sql, $method );
}

class Database implements IDatabase {
	public function query( $sql, $method ) {
		// do some stuff
		return (object)[ 'some_field' => 'some value' ];
	}

}

class MysqlDatabase extends Database {
}
