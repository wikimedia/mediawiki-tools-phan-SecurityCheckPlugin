<?php

class Foo {

	/** @var string $myProp */
	public static $myProp = '';

}

Foo::$myProp = $_GET['evil'];
$a = Foo::$myProp;
echo $a;
