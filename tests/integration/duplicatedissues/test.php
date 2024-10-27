<?php

/** @return-taint html */
function getHTML() {
	return 'placeholder';
}

class TestDuplicateIssues {
	private $val;

	function firstSet() {
		$this->val = getHTML();
		echo $this->val; // TODO: Ideally, we'd want a single issue emitted at this line (T290515). TODO: For the second issue, line 17 should be immediately after 21.
	}

	function setVal( $x ) {
		$this->val = $x;
	}

	function secondSet() {
		$this->setVal( $_GET['a'] );
	}
}
