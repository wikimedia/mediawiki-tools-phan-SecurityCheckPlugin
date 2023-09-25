<?php

class DifferentTaints {
	public $prop;

	public function htmlsafe() {
		$evil = $_GET['foo'];
		$this->prop = htmlspecialchars( $evil );
	}

	public function shellsafe() {
		$evil = $_GET['bar'];
		$this->prop = escapeshellarg( $evil );
	}

	public function sqlsafe() {
		$evil = $_GET['baz'];
		$this->prop = sqlEscaper( $evil );
	}
}

$f = new DifferentTaints();

$f->htmlsafe();
`$f->prop`;
sqlSink( $f->prop );

$f->shellsafe();
echo $f->prop;
sqlSink( $f->prop );

$f->sqlsafe();
`$f->prop`;
echo $f->prop;

/**
 * @param-taint $x escapes_sql
 */
function sqlEscaper( $x ) {

}

/**
 * @param-taint $x exec_sql
 */
function sqlSink( $x ) {

}