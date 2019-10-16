<?php

class Good {
	public static function getMyInstance() {
		return new self;
	}
	public static function getStaticInstance() {
		return new static;
	}

	public function printArg( $arg ) {
		echo htmlspecialchars( $arg );
	}
	public function __toString() : string {
		return 'safe';
	}
}
class Bad extends Good {
	public static function getMyInstance() {
		return new self;
	}
	public static function getParent() {
		return new parent;
	}

	public function printArg( $arg ) {
		echo $arg;
	}
	public function __toString() : string {
		return $_GET['unsafe'];
	}
}

$a1 = Good::getMyInstance();
$b1 = Bad::getMyInstance();

$a2 = Good::getStaticInstance();
$b2 = Bad::getStaticInstance();

$a3 = Bad::getParent();

$safe = 'foobar';
$unsafe = $_GET['foobar'];

// phpcs:disable Generic.Files.LineLength
// Calls on $a* are always safe, calls on $b* depend on the param
$a1->printArg( $safe );
$a1->printArg( $unsafe );
$b1->printArg( $safe );
$b1->printArg( $unsafe ); // Unsafe
$a2->printArg( $safe );
$a2->printArg( $unsafe );
$b2->printArg( $safe );
$b2->printArg( $unsafe ); // NOTE This is unsafe but isn't reported due to wrong type being inferred for $b2, https://github.com/phan/phan/issues/2718
$a3->printArg( $safe );
$a3->printArg( $unsafe );

// The __toString method is only safe for $a*
echo $a1;
echo $a2;
echo $a3;
echo $b1;
echo $b2; // NOTE This is unsafe but isn't reported due to wrong type being inferred for $b2, https://github.com/phan/phan/issues/2718
