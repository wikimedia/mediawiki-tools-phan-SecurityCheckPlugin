<?php

// Regression test for a bug where we would add caused-by lines to the body of annotated functions

class TestPreserve {
	/**
	 * @return-taint tainted
	 */
	private function asValuesOfArrayWithUnsafeKeys( array $values ): array {
		$ret = [];
		foreach ( $values as $value ) {// This and the following line must not be in caused-by.
			$ret[$_GET['a']] = strval( $value );
		}
		return $ret;
	}

	public function main() {
		$data = $this->asValuesOfArrayWithUnsafeKeys( $_GET['a'] );
		foreach ( $data as $key => $val ) {
			echo $key;
		}
	}
}

class TestSink {
	/**
	 * @return-taint tainted
	 */
	private function execArgAndReturnTainted( array $values ): array {
		$ret = [];
		foreach ( $values as $value ) {// This and the following line must be in caused-by
			echo $value;
			$ret[] = $_GET['a'];
		}
		return $ret;
	}

	public function main() {
		$this->execArgAndReturnTainted( $_GET['a'] );
	}
}
