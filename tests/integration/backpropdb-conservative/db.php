<?php
namespace Wikimedia\Rdbms;

interface IReadableDatabase {
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	);
}

interface IDatabase extends IReadableDatabase {
}

class Database implements IDatabase {
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		return [ (object)[ 'some_field' => 'some value' ] ];
	}
}

class MysqlDatabase extends Database {
}
