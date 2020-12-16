<?php
class OutputPage {

	public $mBodytext = '';

	public function addHTML( $text ) {
		$this->mBodytext .= $text;
	}

	public function getHTML() {
		return $this->mBodytext;
	}

	public function output() {
		echo $this->mBodytext;
	}
}
