<?php

// Regression test for when passing a tainted argument by ref would mark the parameter as tainted for any other call.
// See T250689 and the 'passbyrefdifferenttaints' test case, of which this is a more complicated version involving dependencies.

class ApiMobileView {
	public function execute() {
		$this->addEvil( $_GET );
	}

	public function addEvil( $param ) {
		$pager = new LogPager();
		$pager->mLimit = $param; // This forces an immediate analysis of LogPager
	}
}

class SpecialMobileWatchlist {
	protected function doFeedQuery() {
		ChangeTags::modifyDisplayQuery( $tables );
		echo $tables; // No taint is involved here, so this is safe.
	}
}

class ChangeTags {
	public static function modifyDisplayQuery( &$tables ) {
	}
}

class LogPager {
	public $mLimit;
	private $mConds;

	private function limitType() {
		$this->mConds = $_GET;
	}

	public function getQueryInfo() {
		ChangeTags::modifyDisplayQuery( $this->mConds ); // This is tainted, but shouldn't affect the echo above
	}
}
