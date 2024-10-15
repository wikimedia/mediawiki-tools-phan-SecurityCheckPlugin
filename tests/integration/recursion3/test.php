<?php

class TestRecursion {
	private static function doRecursion( $data ) {
		$ret = 'safe';
		if ( rand() ) {
			$ret .= self::doRecursion( $data );
		}
		return $ret;
	}

	public function execute() {
		echo self::doRecursion( $_GET['a'] ); // Safe
	}
}
