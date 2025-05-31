<?php

class UnsafeAsString {
	public function __toString(): string {
		return $_GET['a'];
	}
}

interface UnsafeFactoryInterface {
	public function getUnsafeObj(): UnsafeAsString;
}

class UnsafeFactory implements UnsafeFactoryInterface {
	public function getUnsafeObj(): UnsafeAsString {
		return new UnsafeAsString();
	}
}

function testWithInterface( UnsafeFactoryInterface $factory ) {
	echo $factory->getUnsafeObj(); // Must be flagged as XSS (TODO: line 5 in caused-by)
}

function testWithImplementation( UnsafeFactory $factory ) {
	echo $factory->getUnsafeObj(); // Must be flagged as XSS, caused by 15, 5
}
