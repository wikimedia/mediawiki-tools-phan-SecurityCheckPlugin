<?php

class TestToStringBase {
	protected $value = null;
	public function __construct( $value ) {
		$this->value = $value;
	}

	public function __toString(): string {
		return $this->value;
	}
}

class SafeTestToStringChild extends TestToStringBase {
	public function __toString(): string {
		return htmlspecialchars( $this->value );
	}
}

class TestToStringEvil {
	public function __toString(): string {
		return $_GET['stuff'];
	}
}

class ToStringWithSetter {
	private $val;
	public function setVal( $a ) {
		$this->val = $a;
	}
	public function toString() {
		return $this->val;
	}
}

$unsafe = new TestToStringBase( $_GET['bar'] );
echo "unsafe is $unsafe";
echo ( new TestToStringBase( $_GET['bar'] ) );

echo ( new TestToStringEvil() );
$d = new TestToStringEvil;
echo $d;
$a = new SafeTestToStringChild( "some safe var" );
echo "A is $a";
$b = new SafeTestToStringChild( $_GET['bar'] );
echo "B is $b";

$f = new ToStringWithSetter;
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

