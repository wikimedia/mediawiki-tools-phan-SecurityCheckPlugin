<?php
namespace Wikimedia\Rdbms;

interface IDatabase {
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	);
}

class Database implements IDatabase {
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		return 'placeholder';
	}
}
