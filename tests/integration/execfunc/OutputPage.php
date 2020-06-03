<?php
class OutputPage {

	public $mBodytext = '';

	public function addHTML( $text ) {
		$this->mBodytext .= $text; // TODO: This line should be in caused-by
	}

	public function getHTML() {
		return $this->mBodytext;
	}

	public function output() {
		echo $this->mBodytext;
	}
}
