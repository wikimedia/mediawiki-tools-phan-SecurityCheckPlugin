<?php

class BackpropNumkeyFPValueObject {
	/** @var mixed */
	public $value;

	public function getValue() {
		return $this->value;
	}

	public function setValue( $value ) {
		$this->value = $value;
	}
	public static function copyFromOther( self $other ) {
		$result = new static();
		$result->value =& $other->value;

		return $result;
	}
}

class BackpropNumkeyFPClass1 {
	protected $prop1 = [];

	public function method1( $path, $sourceType = null ) {
		$valueObj = new \BackpropNumkeyFPValueObject();
		$objValue = $valueObj->value;
		$dbw = new Database;
		$this->prop1['foo'] = [
			'string' => $objValue,
		];
		$dbw->select( 'uploadstash', '*', $this->prop1['foo'] ); // NOT an SQLi, and it should NOT backpropagate numkey on prop1
	}

}

class BackpropNumkeyFPClass2 {
	private function method2() {
		$result = new \BackpropNumkeyFPValueObject();
		$result->setValue( $_GET['a'] ); // NOT an SQLi caused by lines 27-29-32
		return $result;
	}
}


class BackpropNumkeyFPClass3 {
	public function method3() {
		$prop = BackpropNumkeyFPValueObject2::createAndGetProp();
		$obj2 = BackpropNumkeyFPValueObject2::newFromString( $prop );
		$status = new BackpropNumkeyFPValueObject();
		$status->setValue( $obj2 ); // NOT an SQLi caused by lines 27-29-32
	}
}

class BackpropNumkeyFPValueObject2 {
	private $stringProp;

	private function __construct( string $val ) {
		$this->stringProp = $val;
	}

	public static function createAndGetProp() {
		return self::newFromString( 'x' )->stringProp;
	}

	public static function newFromString( $val ) {
		return self::newFromKV( 'string', $val );
	}

	private static function newFromKV( $key, $value ) {
		execNumkey( [ $key => $value ] ); // This should NOT backpropagate numkey on $value
		return new self( $_GET['a'] );
	}
}
