<?php

use Wikimedia\Rdbms\MysqlDatabase;

class DBSelectText {
	/** @var MysqlDatabase */
	private $readConnection;

	private function getUsedEntityIdStrings() {
		$subQueries = $this->readConnection->selectSQLText(
			'foo',
			'eu_entity_id'
		);
		if ( true ) {
			$this->getUsedEntityIdStringsMySql( $subQueries );
		}
		$this->readConnection->query( $subQueries, __METHOD__ );
	}

	// Note: The parameter name here is the same as the variable name above,
	// which is required to trigger issue.
	private function getUsedEntityIdStringsMySql( $subQueries ) {
	}

}
