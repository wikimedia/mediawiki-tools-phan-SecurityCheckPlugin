<?php

class ErrorReportNew {
	public function __construct( $foo ) {
		echo $foo;
	}
}

$evil = $_GET['evil'];
new ErrorReportNew( $evil );
