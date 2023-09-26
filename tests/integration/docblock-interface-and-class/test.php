<?php

/*
 * Test to verify that if an interface method is annotated, then all classes having that
 * interface in their ancestor list retain the annotated information.
 */

interface BaseInterface {
	/**
	 * @param-taint $s escapes_html
	 * @return-taint none
	 */
	public function escapeHTMLAlwaysOverridden( string $s ) : string;
	/**
	 * @param-taint $s escapes_html
	 * @return-taint none
	 */
	public function escapeHTMLOverriddenOnlyInParent( string $s ) : string;
}

class ParentClass implements BaseInterface {
	public function escapeHTMLAlwaysOverridden( string $s ): string {
		return $s; // Still safe because of interface annotation
	}

	public function escapeHTMLOverriddenOnlyInParent( string $s ): string {
		return $s; // Still safe because of interface annotation
	}
}

class ChildClass extends ParentClass {
	public function escapeHTMLAlwaysOverridden( string $s ): string {
		return $s . 'still safe!';
	}
}

function testInterface( BaseInterface $x ) {
	echo $x->escapeHTMLAlwaysOverridden( $_GET['a'] ); // Safe
	echo $x->escapeHTMLOverriddenOnlyInParent( $_GET['a'] ); // Safe
}
function testParent( ParentClass $x ) {
	echo $x->escapeHTMLAlwaysOverridden( $_GET['a'] ); // Safe
	echo $x->escapeHTMLOverriddenOnlyInParent( $_GET['a'] ); // Safe
}
function testChild( ChildClass $x ) {
	echo $x->escapeHTMLAlwaysOverridden( $_GET['a'] ); // Safe
	echo $x->escapeHTMLOverriddenOnlyInParent( $_GET['a'] ); // Safe
}