<?php

// Regression test for a bug where method links were passed through unconditionally in caused-by lines for all function
// calls, resulting in extraneous lines.

class CausedByArgLinks {
	private $prop = '';

	public function main() {
		$unsafe = [ $_GET['a'] ];
		$val = $this->appendToPropAndReturnNoOp( $unsafe ) . $this->prop;
		echo $val;
	}

	private function appendToPropAndReturnNoOp( array $arr ) {
		foreach ( $arr as $el ) {
			$this->prop .= $el;
			return $this->doNoop( $el );
		}
	}

	private function doNoop( $unused ) {
		// For the bug to be reproducible, this function must take an argument and return an array.
		return [ 'sth' ];
	}
}
