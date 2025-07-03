<?php

$a = new BackpropOverride;
$b = $_GET['evil'] . 'foo';
$a->indirect( $b );

class BackpropOverride {
	/**
	 * @param-taint $val exec_sql_numkey
	 */
	public function doStuff( $val ) {
		return 42;
	}

	public function indirect( $value ) {
		$this->doStuff( [ 'key' => $value ] );
	}
}
