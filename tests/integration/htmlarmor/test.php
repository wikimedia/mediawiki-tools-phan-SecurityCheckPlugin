<?php

class HtmlArmor {
	public function __construct( $value ) {
	}
	public function __toString() {
		return 'placeholder';
	}
}

$arm1 = new HtmlArmor( $_GET['unsafe'] );
$arm2 = new HtmlArmor( 'safe' );
