<?php

/*
 * WARNING: Lots of black-magic going on here!
 * Regression test to ensure that we don't over-link variables.
 * Trying to do so would result in basically everything in this test being linked together,
 * and assigning a tainted value to a prop would in turn cause all the methods in ClassWithManyProps
 * to be reanalyzed, and so on in an endless (?) loop. Analyzing this file with a "sane" version
 * of taint-check will take a split second, but trying to do so with a faulty version will likely
 * reach PHP's execution limit, or time out the build, or annoy you and your IDE -- either way,
 * a failure won't go unnoticed.
 */

function setFirstToSecond( &$dest, $source ) {
	$temp = $dest;
	if ( $source !== null ) {
		$dest = $source;
	}
	return $temp;
}

class ManipulatesClassWithManyProps {
	/** @var ClassWithManyProps */
	public $classWithManyProps;

	public function method1() {
		$this->classWithManyProps->set1516( $_GET['x'] );
		$this->classWithManyProps->set1( $_GET['x'] );
		return $this->classWithManyProps;
	}

	private function method2() {
		$toc = htmlspecialchars( '' );
		$this->classWithManyProps->set2( $toc );
	}

	public function method3() {
		$this->method4( $_GET['X'] );
	}

	private function method4( $value ) {
		$this->classWithManyProps->set13( $value );
	}
}

class ClassWithArrayProp {
	private $arrayProp;

	public function setArrayPropElement( $x ) {
		return setFirstToSecond( $this->arrayProp['x'], $x );
	}
}


class ClassWithManyProps {
	private $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12, $p13, $p14, $p15, $p16;

	public function set1( $par ) { setFirstToSecond( $this->p1, $par ); }

	public function set2( $par ) { setFirstToSecond( $this->p2, $par ); }

	public function set3( $par ) { setFirstToSecond( $this->p3, $par ); }

	public function set4( $par ) { setFirstToSecond( $this->p4, $par ); }

	public function set5( $par ) { setFirstToSecond( $this->p5, $par ); }

	public function set6( $par ) { setFirstToSecond( $this->p6, $par ); }

	public function set7( $par ) { setFirstToSecond( $this->p7, $par ); }

	public function set8( $par ) { setFirstToSecond( $this->p8, $par ); }

	public function set9( $par ) { setFirstToSecond( $this->p9, $par ); }

	public function set10( $par ) { setFirstToSecond( $this->p10, $par ); }

	public function get11() { return $this->p11; }

	public function get12() { return $this->p12; }

	public function set13( $par ) { return setFirstToSecond( $this->p13, $par ); }

	public function set14( $value ) { $this->p14 = $value; }

	public function get14() { return $this->p14; }

	public function set1516( $value ) {
		$this->p15 = $value;
		$this->p16 = $value;
	}

	public function mergeWithOther( ClassWithManyProps $other ) {
		$this->p1 = $other->p1;
		$this->p2 = $other->p2;
		$this->p3 = self::mergeList( $other->p3 );
		$this->p4 = $other->p4;
		$this->p5 = $other->p5;
		$this->p6 = $other->p6;
		$this->p7 = $other->p7;
		$this->p8 = $other->p8;
		$this->p9 = $other->p9;
		$this->p10 = $other->p10;
		$this->p11 = $other->p11;
		$this->p12 = $other->p12;
		$this->p13 = self::mergeList( $other->p13 );
		$this->p14 = self::mergeList( $other->p14 );
	}

	private static function mergeList( array $a ) {
		return array_merge( $a, [] );
	}
}


class TriggerLinkingTimeout {
	public function createAndSetProp() {
		$op = new ClassWithArrayProp();
		$op->setArrayPropElement( false );
	}
}
