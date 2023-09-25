<?php
class OutputBufferingClass {

	public $buffer = '';

	public function addHTML( $text ) {
		$this->buffer .= $text;
	}

	public function getHTML() {
		return $this->buffer;
	}

	public function output() {
		echo $this->buffer;
	}
}
