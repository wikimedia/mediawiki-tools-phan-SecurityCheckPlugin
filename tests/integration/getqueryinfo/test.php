<?php
class SpecialPage {
}
class QueryPage extends SpecialPage {
	public function getQueryInfo() {
		return null;
	}
}

class MySpecialPage extends QueryPage {
	public function getQueryInfo() {
		$prefix = $_GET['prefix'];
		return [
			'tables' => [ 'pagelinks' ],
			'fields' => [
				'namespace' => 'pl_namespace',
				'title' => "CONCAT( '$prefix', pl_title)",
				'value' => 'COUNT(*)',
				'page_namespace'
			],
			'options' => [
				'HAVING' => $_GET['evil'],
				'GROUP BY' => [
					'pl_namespace', 'pl_title',
					'page_namespace', $_GET['evil']
				]
			],
			'join_conds' => [
				'page' => [
					'LEFT JOIN',
					[
						'page_namespace = pl_namespace',
						'page_title = pl_title',
						"page_size = " . $_GET['size']
					]
				]
			],
			'conds' => [
				$_GET['somethingEvil']
			]
		];
	}
}

class MySpecialPage2 extends QueryPage {
	public function getQueryInfo() {
		// This should be safe.
		return [
			'tables' => 'someTable',
			'fields' => [ 'value' => 'COUNT(*)' ],
			'options' => [
				'HAVING' => [ 'value' => $_GET['count'] ]
			]
		];
	}
}
