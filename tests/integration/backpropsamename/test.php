<?php

// Regression test for a bug where if inside a branch you called a function with a semi-tainted variable as its
// argument, and the formal parameter name used by the function was the same as the variable name of the argument,
// then the variable becomes fully tainted.

class BackpropSameName {
	private function mainFunc() {
		$allButHTMLTaint = htmlspecialchars( $_GET['x'] );
		if ( true ) {
			$this->otherFunc( $allButHTMLTaint );// This call must not change taintedness of $allButHTMLTaint
		}
		echo $allButHTMLTaint; // Safe
	}

	// Note: The parameter name here is the same as the variable name above,
	// which is required to trigger issue.
	private function otherFunc( $allButHTMLTaint ) {
	}

}
