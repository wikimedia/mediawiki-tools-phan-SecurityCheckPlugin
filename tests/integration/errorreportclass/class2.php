<?php

class ErrorReportClass2 {
	private $bazMember;

	public function __construct( $valBaz ) {
		$this->bazMember = new ErrorReportClass1;
		$this->bazMember->setFooMember( $valBaz );
	}

	public function echoVal() {
		echo $this->bazMember->getFooMember();
	}
}

$b = new ErrorReportClass2( $_GET['evil'] );
