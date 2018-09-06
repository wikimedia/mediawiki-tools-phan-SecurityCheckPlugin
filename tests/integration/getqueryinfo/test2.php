<?php

use Wikimedia\Rdbms\MysqlDatabase;

class Foo {
	public function getQueryInfo() {
		return [
			'tables' => [ 'pagelinks' ],
			'fields' => [
				'namespace' => [ 1, 2 ],
			],
			'conds' => [
				'foo' => $_GET['somethingEvil']
			]
		];
	}

	public function doQuery() {
		$dbr = new MysqlDatabase;
		$qi = $this->getQueryInfo();
		return $dbr->select(
			$qi['tables'],
			$qi['fields'],
			$qi['conds'],
			__METHOD__
		);
	}
}
$a = new Foo;
$a->doQuery();
