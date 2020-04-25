<?php

class Clazz {
	public function execute() {
		// Only unsafe() and mixed() should be responsible for taintedness -- T250679
		echo $this->safe1() .
			$this->safe2() .
			'foo' .
			$this->unsafe() .
			'bar' .
			$this->safe3() .
			'baz' .
			$this->mixed();
	}

	private function safe1() {
		if ( rand() ) {
			return 'safe';
		}
		return htmlspecialchars( $_GET['aa'] );
	}

	private function safe2() {
		return rand() ? 'safe' : htmlspecialchars( $_GET['x'] );
	}

	private function safe3() {
		return htmlspecialchars( $_GET['y'] );
	}

	private function unsafe() {
		return $_GET['x'];
	}

	private function mixed() {
		if ( rand() ) {
			return htmlspecialchars( $_GET['xxx'] );
		}
		return $_GET['x'];
	}
}
