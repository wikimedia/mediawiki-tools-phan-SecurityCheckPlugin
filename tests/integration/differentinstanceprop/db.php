<?php
namespace Wikimedia\Rdbms;

interface IDatabase {
	public function selectRow(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	);
}

class Database implements IDatabase {
	public function selectRow(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		return (object)[ 'some_field' => 'some value' ];
	}
}

class MysqlDatabase extends Database {
	public function getType() {
		return 'mysql';
	}
}
