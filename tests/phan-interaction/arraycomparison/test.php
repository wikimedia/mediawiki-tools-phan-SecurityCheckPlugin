<?php

class PermissionManager {
	public function userCan( string $action ) {
		$this->getPermissionErrorsInternal( $action );
	}

	private function getPermissionErrorsInternal( $action ) {
		$this->checkPermissionHooks( [] );
	}

	private function checkPermissionHooks( $errors ) {
		$this->resultToError( [] );
	}

	/**
	 * @param array|string|false $result
	 */
	private function resultToError( $result ) {
		if ( is_array( $result ) && rand() ) {
			$errors = 1;
		} elseif ( $result !== '' ) { // Avoid: "PhanTypeComparisonFromArray array to string comparison"
			$errors = 3;
		}
	}
}
