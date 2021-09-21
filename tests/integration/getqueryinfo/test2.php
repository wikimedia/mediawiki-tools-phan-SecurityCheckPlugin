<?php

use Wikimedia\Rdbms\MysqlDatabase;

class SafeGetQueryInfo {
	public function getQueryInfo() {
		return [
			'tables' => [ 'pagelinks' ],
			'fields' => [
				'namespace' => [ 1, 2 ],
			],
			'conds' => [
				'foo' => $_GET['somethingEvil'] // Make sure this doesn't cause an SQLi
			]
		];
	}

	public function doQuery() {
		$dbr = new MysqlDatabase;
		$qi = $this->getQueryInfo();
		// This is safe
		return $dbr->select(
			$qi['tables'],
			$qi['fields'],
			$qi['conds'],
			__METHOD__
		);
	}
}
$safe = new SafeGetQueryInfo;
$safe->doQuery();


class UnsafeGetQueryInfo {
	public function getQueryInfo() {
		return [
			'tables' => [ 'pagelinks' ],
			'fields' => [
				'namespace' => [ 1, 2 ],
			],
			'conds' => [
				$_GET['somethingEvil'] // This one should cause an SQLi
			]
		];
	}

	public function doQuery() {
		$dbr = new MysqlDatabase;
		$qi = $this->getQueryInfo();
		// This is unsafe
		return $dbr->select(
			$qi['tables'],
			$qi['fields'],
			$qi['conds'],
			__METHOD__
		);
	}
}
$unsafe = new UnsafeGetQueryInfo;
$unsafe->doQuery();
