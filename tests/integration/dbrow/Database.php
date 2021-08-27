<?php

namespace Wikimedia\Rdbms;

class Database {
	function selectRow( $table, $vars, $conds = '', $x = 'x', $o = [], $j = [] ) {
		return (object)[ $_GET['key'] => $_GET['val'] ];
	}
}
