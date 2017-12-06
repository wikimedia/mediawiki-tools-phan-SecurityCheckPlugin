<?php
namespace OOUI;

class Layout {

	protected $contents;
	public function __toString() {
		return $this->contents;
	}

}

class HorizontalLayout extends Layout {
	public function __construct( $contents ) {
		$this->contents = $contents;
	}

}
