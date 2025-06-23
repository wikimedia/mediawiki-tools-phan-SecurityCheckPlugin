<?php
namespace Wikimedia\Rdbms;

interface IReadableDatabase {
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	);
}

interface IDatabase extends IReadableDatabase {
	public function insert( $table, $a, $fname = __METHOD__, $options = [] );
}

class Database implements IDatabase {
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		return (object)[ 'some_field' => 'some value' ];
	}
	public function insert( $table, $a, $fname = __METHOD__, $options = [] ) {
		return true;
	}
}

class MysqlDatabase extends Database {
}
