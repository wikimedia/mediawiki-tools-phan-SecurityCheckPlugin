<?php

class HardcodedXSSParamConstructor {
	public function __construct( $value ) {
	}
}

$unsafe = new HardcodedXSSParamConstructor( $_GET['unsafe'] );
$safe = new HardcodedXSSParamConstructor( 'safe' );
