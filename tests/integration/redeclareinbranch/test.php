<?php

class Foo {
	public function show() {
		echo $this->bar();
	}

	/**
	 * @return string
	 */
	private function bar() {
		if ( rand() ) {
			// This is obviously safe due to reassigning, but we have to ensure
			// that taintedness is overwritten in BranchScope.
			$form = $_GET['bar'];
			$form = 'foo';
			return $form;
		}
		return 'foo';
	}

	public function output() {
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
