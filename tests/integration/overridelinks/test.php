<?php

function overrideKnownElement( $x ) {
	$arr = [];
	$arr['foo'] = $x;
	// Should clear links from $x
	$arr['foo'] = 'safe';
	echo $arr['foo'];
}
overrideKnownElement( $_GET['a'] ); // Safe

function overrideUnknownElement( $x ) {
	$arr = [];
	$k1 = rand();
	$arr[$k1] = $x;
	// Should NOT clear links in the unknown key
	$k2 = rand();
	$arr[$k2] = 'safe';
	echo $arr[$k2];
}
overrideUnknownElement( $_GET['a'] ); // Unsafe

class TestWithProp {
	private $prop1, $prop2;

	function overrideKnownElement( $x ) {
		$this->prop1 = [];
		$this->prop1['foo'] = $x;
		// Should not clear links from $x, since we never override for props
		$this->prop1['foo'] = 'safe';
		echo $this->prop1['foo'];//@phan-suppress-current-line SecurityCheck-XSS not relevant for this test
	}
	function test1() {
		$this->overrideKnownElement( $_GET['a'] ); // Unsafe (though ideally safe)
	}

	function overrideUnknownElement( $x ) {
		$this->prop2 = [];
		$k1 = rand();
		$this->prop2[$k1] = $x;
		// Should NOT clear links in the unknown key, regardless of using a prop
		$k2 = rand();
		$this->prop2[$k2] = 'safe';
		echo $this->prop2[$k2];//@phan-suppress-current-line SecurityCheck-XSS not relevant for this test
	}
	function test2() {
		$this->overrideUnknownElement( $_GET['a'] ); // Unsafe
	}
}
