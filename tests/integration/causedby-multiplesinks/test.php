<?php

class CausedByMultipleSinks {
	private $prop;

	public function setPropAndExecK1( $arg1 ): void {
		$this->prop = $arg1;
		echo $this->prop['k1'];
	}

	public function setPropAndExecK2( $arg2 ): void {
		$this->prop = $arg2;
		echo $this->prop['k2'];
	}
}

$t = new CausedByMultipleSinks();

$t->setPropAndExecK1( [ 'k1' => 'safe' ] ); // Safe, but allows the `echo` to backpropagate taintedness
$t->setPropAndExecK2( [ 'k1' => $_GET['b'] ] ); // Unsafe, caused by 12, 8

function execXAndY( $arg ): void {
	$local = $arg;
	echo $local['x'];
	echo $local['y'];
}

execXAndY( $_GET['a'] ); // Unsafe, caused by lines 25, 24, 23
execXAndY( [ 'x' => $_GET['a'], 'y' => $_GET['a'] ] ); // Unsafe, caused by lines 25, 24, 23
execXAndY( [ 'x' => $_GET['a'] ] ); // Unsafe, caused by lines 24, 23
execXAndY( [ 'y' => $_GET['a'] ] ); // Unsafe, caused by lines 25, 23
