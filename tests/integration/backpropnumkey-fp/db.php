<?php

namespace Wikimedia\Rdbms;

class Database {
	public function selectRow( $table, $vars, $conds, $fname = __METHOD__, $options = [], $join_conds = [] ){}
	public function select( $table, $fields, $conds ){}
}
