<?php

// Do not pre-analyze doSink()

new ShapedVarLinksNoWarmup( $_GET['a'] );

class ShapedVarLinksNoWarmup {
	private $myProp;

	public function doSink() {
		sink( $this->myProp ); // Shell
	}

	public function __construct( $par ) {
		$this->myProp = [ 'safe' => 'x', 'unsafe' => $par ];
	}
}

function sink( $x ) {
	echo $x['safe'];
	shell_exec( $x['unsafe'] );
}
