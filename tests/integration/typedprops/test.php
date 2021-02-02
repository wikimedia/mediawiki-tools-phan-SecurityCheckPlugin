<?php

class Properties {
	public string $stringProp;
	public int $intProp;
}

$class = new Properties;
$class->stringProp = 'safe';
echo $class->stringProp; // Safe
$class->intProp = 2;
echo $class->intProp; // Safe
$class->stringProp = $_GET['unsafe'];
echo $class->stringProp; // Unsafe
$class->intProp = $_GET['alsounsafe'];
echo $class->intProp; // Safe!
