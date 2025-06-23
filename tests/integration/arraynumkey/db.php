<?php

namespace Wikimedia\Rdbms\Platform {
	interface ISQLPlatform {
		public function selectSQLText(
			$table, $vars, $conds = '', $fname = __METHOD__,
			$options = [], $join_conds = []
		);
	}
}

namespace Wikimedia\Rdbms {
	use Wikimedia\Rdbms\Platform\ISQLPlatform;

	interface IReadableDatabase extends ISQLPlatform {
		public function select(
			$table, $vars, $conds = '', $fname = __METHOD__,
			$options = [], $join_conds = []
		);

		public function selectRow(
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
	}
}