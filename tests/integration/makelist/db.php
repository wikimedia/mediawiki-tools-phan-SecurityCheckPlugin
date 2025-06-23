<?php

namespace Wikimedia\Rdbms\Platform {
	interface ISQLPlatform {
		const LIST_COMMA = 0;
		const LIST_AND = 1;
		const LIST_SET = 2;
		const LIST_NAMES = 3;
		const LIST_OR = 4;
		public function makeList( $a, $mode = self::LIST_COMMA );
	}
}

namespace Wikimedia\Rdbms {
	use Wikimedia\Rdbms\Platform\ISQLPlatform;

	interface IDatabase extends ISQLPlatform {
		public function query( $sql, $method );

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
	}

	class MysqlDatabase extends Database {
	}
}
