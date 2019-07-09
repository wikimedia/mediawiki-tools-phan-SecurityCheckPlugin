<?php

use \Wikimedia\Rdbms\Database;

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
		$db = new Database;
		$this->prop = $db->addIdentifierQuotes( $evil );
	}
}

$f = new Foo;
$db = new Database();

$f->htmlsafe();
`$f->prop`;
$db->query( $f->prop );

$f->shellsafe();
echo $f->prop;
$db->query( $f->prop );

$f->sqlsafe();
`$f->prop`;
echo $f->prop;
