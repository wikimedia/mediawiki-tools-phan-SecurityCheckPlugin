<?php

$a = new Prop3;

class Prop3 {

	/** @var string $myProp */
	public $myProp = '';

	public function setMyProp( $p ) {
		$this->myProp = $p;
	}

	public function echoMyProp() {
		return $this->myProp;
	}

}

$a->setMyProp( $_GET['do'] );
echo $a->echoMyProp();

class Prop3_Test2 {

	/** @var string $myProp */
	public $myProp = '';

	public function setMyProp( $p ) {
		$this->myProp = $p;
	}

	public function echoMyProp() {
		return $this->myProp;
	}

}

// Ensure that code order doesn't matter.
$b = new Prop3_Test2;
$cb = function () use ( $b ) {
	echo $b->echoMyProp();
};
$b->setMyProp( $_GET['evil'] );
$cb();
