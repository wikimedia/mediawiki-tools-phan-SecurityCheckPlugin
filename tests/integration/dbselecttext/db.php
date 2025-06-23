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

	interface IDatabase extends ISQLPlatform {
		public function query( $sql, $method );

		public function selectSQLText(
			$table, $vars, $conds = '', $fname = __METHOD__,
			$options = [], $join_conds = []
		);
	}

	class Database implements IDatabase {
		public function query( $sql, $method ) {
			// do some stuff
			return (object)[ 'some_field' => 'some value' ];
		}

		public function selectSQLText(
			$table, $vars, $conds = '', $fname = __METHOD__,
			$options = [], $join_conds = []
		) {
			return "query";
		}
	}

	class MysqlDatabase extends Database {
	}
}