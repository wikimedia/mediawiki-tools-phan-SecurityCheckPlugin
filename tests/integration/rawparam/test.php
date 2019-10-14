<?php

class Message {
	private $foo = 'placeholder';
	public function text() {
		return $this->foo;
	}
	public function escaped() {
		return $this->foo;
	}
	public function rawParams( $params ) {
		$this->foo = $params;
		return $this;
	}
}

class HtmlArmor {
	public function __construct( $x ) {
	}
	public function __toString() {
		return 'placeholder';
	}
}

$m = new Message;
// These are all unsafe, there's not even need to output the result
// @todo Should we also mark the echo's as XSS?
$m->rawParams( $_GET['baz'] );
$m->rawParams( $_GET['baz'] )->text();
echo $m->rawParams( $_GET['baz'] )->escaped();
echo htmlspecialchars( $m->rawParams( $_GET['baz'] )->text() );
echo htmlspecialchars( $m->rawParams( $_GET['baz'] )->escaped() ); // This is also double escaped

$h = new HtmlArmor( $_GET['baz'] );
echo $h;
echo htmlspecialchars( $h );

class Foo {
	/**
	 * @param-taint $string raw_param,tainted
	 */
	public static function allTaint( $string ) {
	}
	/**
	 * @param-taint $string raw_param,html
	 */
	public static function htmlTaint( $string ) {
	}
	/**
	 * @param-taint $string raw_param
	 */
	public static function noRealTaint( $string ) {
		// raw_param alone does nothing
	}
}

Foo::allTaint( $_GET['bar'] );
Foo::htmlTaint( $_GET['foo'] );
Foo::noRealTaint( $_GET['foo'] );
