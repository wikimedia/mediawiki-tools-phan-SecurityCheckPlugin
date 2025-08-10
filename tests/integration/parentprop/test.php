<?php

namespace TestParentProp;

class ParentClass {
	protected $prop;
	public function __toString() {
		return $this->prop;
	}
}

class ChildClass extends ParentClass {
	public function __construct( $contents ) {
		$this->prop = $contents;
	}
}

$safeInstance = new ChildClass( "More stuff" );
echo $safeInstance; // Safe

$unsafeInstance = new ChildClass( $_GET['stuff'] );
echo $unsafeInstance; // TODO: Unsafe
