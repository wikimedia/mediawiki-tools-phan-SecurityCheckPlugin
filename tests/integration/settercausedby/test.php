<?php

class SetterCausedBy {
	public $propParam;
	public $propDirect;

	public function setPropParam( $x ) {
		$this->propParam = $x;
	}

	public function setPropDirect() {
		$this->propDirect = $_GET['x'];
	}
}

function echoArg( $y ) {
	echo $y;
}

function doEvil() {
	$obj = new SetterCausedBy();
	echoArg( $obj->propParam );
	echoArg( $obj->propDirect );
}

$globalObj = new SetterCausedBy();
$globalObj->setPropParam( $_GET['a'] );
$globalObj->setPropDirect();
