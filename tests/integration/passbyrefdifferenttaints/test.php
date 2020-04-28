<?php

// Regression test for when passing a tainted argument by ref would mark the parameter as tainted for any other call.
// See T250689 and the 'passbyrefdependencies' test case (a more complicated version involving taint dependencies).


class LogPager {
	public function getQueryInfo() {
		$x = $_GET; // Note, this intermediate assignment is necessary
		self::modifyDisplayQuery( $x );
	}

	protected function doFeedQuery() {
		self::modifyDisplayQuery( $tables );
		echo $tables; // This is safe: the previous call to modifyDisplayQuery with a tainted argument doesn't count.
	}

	public static function modifyDisplayQuery( &$tables ) {
	}
}
