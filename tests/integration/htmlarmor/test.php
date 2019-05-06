<?php

class HtmlArmor {
	/**
	 * @param string|null $value
	 */
	public function __construct( $value ) {
	}
}

$arm1 = new HtmlArmor( $_GET['unsafe'] ); // Not a good idea, but still safe
$arm2 = new HtmlArmor( 'safe' );
