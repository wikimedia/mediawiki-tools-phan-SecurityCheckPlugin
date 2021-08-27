<?php

class Foo {
	protected $value = null;
	public function __construct( $value ) {
		$this->value = $value;
	}

	public function __toString(): string {
		return $this->value;
	}
}

class SafeFoo extends Foo {
	public function __toString() {
		return htmlspecialchars( $this->value );
	}
}

class DoEvil {
	public function __toString(): string {
		return $_GET['stuff'];
	}
}

class Foo2 {
	private $val;
	public function setVal( $a ) {
		$this->val = $a;
	}
	public function toString() {
		return $this->val;
	}
}

$unsafe = new Foo( $_GET['bar'] );
echo "unsafe is $unsafe";
echo ( new Foo( $_GET['bar'] ) );

echo ( new DoEvil() );
$d = new DoEvil;
echo $d;
$a = new SafeFoo( "some safe var" );
echo "A is $a";
$b = new SafeFoo( $_GET['bar'] );
echo "B is $b";

$f = new Foo2;
$f->setVal( $_GET['d'] );
$g = $f->toString();
echo $g;



class SafeToString {
	public function __toString(): string {
		return 'safe';
	}
}
class UnsafeToString {
	public function __toString(): string {
		return $_GET['f'];
	}
}

function testMultiple() {
	if ( rand() ) {
		$class = SafeToString::class;
	} else {
		$class = UnsafeToString::class;
	}
	$obj = new $class;
	echo $obj; // Unsafe
}

