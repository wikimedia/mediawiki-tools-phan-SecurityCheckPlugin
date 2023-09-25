<?php

class OutputHandler {
	/**
	 * @param-taint $html exec_html
	 */
	public function addHTML( $html ) {

	}
}

$out = new OutputHandler;

$out->addHTML( $_GET['evil'] );
$out->addHtml( $_GET['evil'] );
$out->addhtml( $_GET['evil'] );
$out->ADDHTML( $_GET['evil'] );
