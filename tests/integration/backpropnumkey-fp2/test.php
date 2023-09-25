<?php

class FileDeleteForm {
	protected function addContentModelChangeLogEntry() {
		( new RecentChange( 'foo' ) )->save();
	}
}
class FileDeleteForm2 {
	public function execute() {
		new RecentChange( $_GET['a'] );
	}
}


class RecentChange {
	public $mAttribs;

	public function save() {
		$dbw = new \Wikimedia\Rdbms\Database();
		$dbw->select( 'recentchanges', '*', $this->mAttribs );// Should not backpropagate NUMKEY
	}

	public function __construct( $logComment ) {
		$this->mAttribs = [ 'rc_comment' => $logComment ];
	}
}
