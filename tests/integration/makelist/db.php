<?php
namespace Wikimedia\Rdbms;

interface IDatabase {
	/** @var int Combine list with comma delimeters */
	const LIST_COMMA = 0;
	/** @var int Combine list with AND clauses */
	const LIST_AND = 1;
	/** @var int Convert map into a SET clause */
	const LIST_SET = 2;
	/** @var int Treat as field name and do not apply value escaping */
	const LIST_NAMES = 3;
	/** @var int Combine list with OR clauses */
	const LIST_OR = 4;

	public function query( $sql, $method );
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	);
	public function makeList( $a, $mode = self::LIST_COMMA );
}

class Database implements IDatabase {
	public function query( $sql, $method ) {
		// do some stuff
		return (object)[ 'some_field' => 'some value' ];
	}

	public function makeList( $a, $mode = self::LIST_COMMA ) {
		return $a[0];
	}

	public function select(
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
