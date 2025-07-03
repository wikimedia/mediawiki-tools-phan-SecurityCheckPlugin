<?php

class Prop4 {

	/** @var string $myProp */
	public $myProp = '';

	public function setMyProp( $p ) {
		$this->myProp = $p;
	}

	public function echoMyProp() {
		// @todo Currently the plugin cannot
		// handle something like $a = $this->myProp; return $a;
		// In the case where the output line is encountered before
		// the set line.
		return $this->myProp;
	}

}

/**
 * @param string[] $in
 * @return bool
 * @param-taint $in exec_html
 */
function out( array $in ) {
	return true;
}

$a = new Prop4;
$cb = function () use ( $a ) {
	out( [ 'foo', 'fee', 'foe', 'hum', $a->echoMyProp(), 'baz' ] );
};
$a->setMyProp( $_GET['do'] );
$cb();
