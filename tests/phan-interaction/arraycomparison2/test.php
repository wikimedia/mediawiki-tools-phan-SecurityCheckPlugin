<?php

class User {
	public function load() {
		$lb = new LoadBalancer;
		$lb->getConnection();
	}
}

class LoadBalancer {
	/**
	 * @param string[]|string|bool $groups
	 */
	private function resolveGroups( $groups ) {
		if ( rand() && $groups !== [] && $groups !== false ) { // Avoid: "PhanTypeComparisonFromArray array to false comparison"
			return;
		}
	}

	public function getConnection( $groups = [] ) {
		$this->resolveGroups( $groups );
	}
}

