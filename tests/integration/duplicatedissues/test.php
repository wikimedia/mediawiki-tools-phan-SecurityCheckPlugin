<?php

/** @return-taint html */
function getHTML() {
	return 'placeholder';
}

class TestDuplicateIssues {
	private $val;

	function firstSet() {
		$this->val = getHTML();
		echo $this->val; // TODO: Ideally, we'd want a single issue emitted at this line (T290515)
	}

	function setVal( $x ) {
		$this->val = $x;
	}

	function secondSet() {
		$this->setVal( $_GET['a'] );
	}
}
