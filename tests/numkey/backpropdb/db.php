<?php
namespace Wikimedia\Rdbms;

interface IDatabase {
	public function query( $sql, $method );
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	);
	public function selectSQLText(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	);
	public function selectRow(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	);
}

class Database implements IDatabase {
	public function query( $sql, $method ) {
		// do some stuff
		return [ (object)[ 'some_field' => 'some value' ] ];
	}

	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		return [ (object)[ 'some_field' => 'some value' ] ];
	}

	public function selectSQLText(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		return 'SELECT * FROM foo';
	}

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
