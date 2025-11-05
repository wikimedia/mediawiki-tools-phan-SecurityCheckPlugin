<?php

class RedeclareInBranch {
	public function outerFunc() {
		echo $this->innerFunc();
	}

	/**
	 * @return string
	 */
	private function innerFunc() {
		if ( rand() ) {
			// This is obviously safe due to reassigning, but we have to ensure
			// that taintedness is overwritten in BranchScope.
			$form = $_GET['bar'];
			$form = 'foo';
			return $form;
		}
		return 'foo';
	}

	public function outputFunc() {
		$form = '';
		if ( rand() ) {
			$form = $_GET['bar'];
			$form = 'foo';
			echo $form; // Safe
			$form = $_GET['baz'];
		}
		echo $form; // Unsafe, and line 25 must not be in its caused-by
	}
}
