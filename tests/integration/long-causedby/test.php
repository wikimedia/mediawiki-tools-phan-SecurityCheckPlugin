<?php

class HardcodedSimpleTaint {
	public static function yesArgReturnsEscaped( $arg ) {
		return 'hardcoded';
	}
}

class TestLongCausedBy {
	public function main() {
		$this->output(
			HardcodedSimpleTaint::yesArgReturnsEscaped(
				HardcodedSimpleTaint::yesArgReturnsEscaped(
					HardcodedSimpleTaint::yesArgReturnsEscaped( $_GET['baz'] )
				)
			)
		);
	}

	private function output( $x ) {
		echo $x;
	}
}
