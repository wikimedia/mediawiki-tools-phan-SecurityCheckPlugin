<?php

// regression test against a bug where if the return value
// of a method was modified by a pass by reference method,
// then the first method was treated as "unknown" which
// resulted in its taint being derived from all it parameters.
class PassByRefNoOp {
	public function funcThatTakesEvil( $evil ) {
		$html = '';
		$this->noOpWithRef( $html );
		return $html;
	}

	private function noOpWithRef( &$s ) {
	}
}

$f = new PassByRefNoOp;
$evil = $_GET['evil'];
echo $f->funcThatTakesEvil( $evil );
