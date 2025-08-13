<?php

$c = new PropertyHolderForNumkey();
$c->doSet( [ $_GET['a'] ]);

$c->doSet( [ 'safe' => $_GET['b'] ]);

$c->doSet( [ $_GET['unsafekey'] => 'safe' ]);

class PropertyHolderForNumkey {
	private $conds;

	public function doSet( array $arr ) {
		$this->conds = $arr;
	}

	public function doExec() {
		execNumkey( $this->conds ); // Unsafe, caused by 4, 8, and 14, but NOT 6
	}
}



