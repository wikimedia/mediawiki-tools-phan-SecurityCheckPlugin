<?php

/** @return-taint html */
function getHTML() {
	return 'placeholder';
}

class TestDuplicateIssues {
	private $val;

	function firstSet() {
		$this->val = getHTML();
		echo $this->val; // Only one issue must be emitted for this line. TODO: with analyze-twice, line 17 should be immediately after 21.
	}

	function setVal( $x ) {
		$this->val = $x;
	}

	function secondSet() {
		$this->setVal( $_GET['a'] );
	}
}
