<?php

class Foo {
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
		$this->prop = mysqli_real_escape_string( new mysqli, $evil );
	}
}

$f = new Foo;
$mysqli = new mysqli;


$f->htmlsafe();
`$f->prop`;
$mysqli->query( $f->prop );

$f->shellsafe();
echo $f->prop;
$mysqli->query( $f->prop );

$f->sqlsafe();
`$f->prop`;
echo $f->prop;
