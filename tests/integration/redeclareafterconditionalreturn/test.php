<?php

class RedeclareAfterConditionalReturn {
	public function outerFunc() {
		echo $this->innerFunc();
	}

	/**
	 * @return string
	 */
	private function innerFunc() {
		// Note that the boolean negation below is necessary.
		if ( !rand() ) {
			return 'foo';
		}

		// This one is obviously safe due to reassigning, but here the scope
		// is BranchScope (added ad hoc by phan due to the return above) and not FunctionLikeScope.
		$form = $_GET['bar'];
		$form = 'foo';
		return $form;
	}
}
