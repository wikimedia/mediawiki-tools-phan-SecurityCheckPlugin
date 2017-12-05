<?php
class GetFetcher {

	/** @var String */
	private $arg;

	private function __construct( $arg ) {
		$this->arg = $arg;
	}

	public static function make( $arg ) {
		return new self( $arg );
	}

	public function get() {
		return $_GET[$this->arg];
	}
}
