<?php
// This test ensures that the call $this->objectRow( $key )
// does not cause $rows to be marked as EXEC_ESCAPED.
class JsonContent {

	protected function objectTable( $mapping ) {
		$rows = [];

		foreach ( $mapping as $key => $val ) {
			$rows[] = $this->objectRow( $key );
		}
		$rows[] = htmlspecialchars( 'f' );
	}

	protected function objectRow( $key2 ) {
		htmlspecialchars( $key2 );
	}
}
