<?php

/*
 * WARNING: Lots of black-magic going on here!
 * Regression test to ensure that we don't over-link variables.
 * Trying to do so would result in basically everything in this test being linked together,
 * and assigning a tainted value to a prop would in turn cause all the methods in ParserOutput
 * to be reanalyzed, and so on in an endless (?) loop. Analyzing this file with a "sane" version
 * of taint-check will take a split second, but trying to do so with a faulty version will likely
 * reach PHP's execution limit, or time out the build, or annoy you and your IDE -- either way,
 * a failure won't go unnoticed.
 */

function wfSetVar( &$dest, $source ) {
	$temp = $dest;
	if ( $source !== null ) {
		$dest = $source;
	}
	return $temp;
}

class Parser {
	/** @var ParserOutput */
	public $mOutput;

	public function parse() {
		$this->mOutput->set1516( $_GET['x'] );
		$this->mOutput->set1( $_GET['x'] );
		return $this->mOutput;
	}

	private function finalizeHeadings() {
		$toc = htmlspecialchars( '' );
		$this->mOutput->set2( $toc );
	}

	public function makeImage() {
		$this->parseLinkParameter( $_GET['X'] );
	}

	private function parseLinkParameter( $value ) {
		$this->mOutput->set13( $value );
	}
}

class ParserOptions {
	private $options;

	public function setAllowUnsafeRawHtml( $x ) {
		return wfSetVar( $this->options['x'], $x );
	}
}


class ParserOutput {
	private $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12, $p13, $p14, $p15, $p16;

	public function set1( $par ) { wfSetVar( $this->p1, $par ); }

	public function set2( $par ) { wfSetVar( $this->p2, $par ); }

	public function set3( $par ) { wfSetVar( $this->p3, $par ); }

	public function set4( $par ) { wfSetVar( $this->p4, $par ); }

	public function set5( $par ) { wfSetVar( $this->p5, $par ); }

	public function set6( $par ) { wfSetVar( $this->p6, $par ); }

	public function set7( $par ) { wfSetVar( $this->p7, $par ); }

	public function set8( $par ) { wfSetVar( $this->p8, $par ); }

	public function set9( $par ) { wfSetVar( $this->p9, $par ); }

	public function set10( $par ) { wfSetVar( $this->p10, $par ); }

	public function get11() { return $this->p11; }

	public function get12() { return $this->p12; }

	public function set13( $par ) { return wfSetVar( $this->p13, $par ); }

	public function set14( $value ) { $this->p14 = $value; }

	public function get14() { return $this->p14; }

	public function set1516( $value ) {
		$this->p15 = $value;
		$this->p16 = $value;
	}

	public function mergeInternalMetaDataFrom( ParserOutput $source ) {
		$this->p1 = $source->p1;
		$this->p2 = $source->p2;
		$this->p3 = self::mergeList( $source->p3 );
		$this->p4 = $source->p4;
		$this->p5 = $source->p5;
		$this->p6 = $source->p6;
		$this->p7 = $source->p7;
		$this->p8 = $source->p8;
		$this->p9 = $source->p9;
		$this->p10 = $source->p10;
		$this->p11 = $source->p11;
		$this->p12 = $source->p12;
		$this->p13 = self::mergeList( $source->p13 );
		$this->p14 = self::mergeList( $source->p14 );
	}

	private static function mergeList( array $a ) {
		return array_merge( $a, [] );
	}
}


class OutputPage {
	public function parserOptions() {
		$op = new ParserOptions();
		$op->setAllowUnsafeRawHtml( false );
	}
}
