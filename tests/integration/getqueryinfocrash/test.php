<?php

class GetQueryInfoCrash {
	public function getQueryInfo() {
		$joinsName = 'joins';
		return [
			'tables' => [],
			'fields' => [],
			'conds' => [],
			'options' => [],
			$joinsName => [], // This would cause a crash due to non-literal key
		];
	}
}
