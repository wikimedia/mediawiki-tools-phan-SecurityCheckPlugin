<?php

class StaticProp {

	/** @var string $myProp */
	public static $myProp = '';

}

StaticProp::$myProp = $_GET['evil'];
$a = StaticProp::$myProp;
echo $a;
