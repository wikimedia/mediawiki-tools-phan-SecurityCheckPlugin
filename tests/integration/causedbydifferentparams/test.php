<?php

/*
 * Note: this test isn't really testing much. Due to how taintedness is tracked for props, caused-by lines are probably
 * going to be quite long for every issue. This test mostly exists so that changes to the caused-by lines for props can
 * be tracked over time. The comments below only specify what is the bare minimum of information that each line should contain.
 */

class CausedByDifferentParams {
	private $prop;

	function sinkTwoParams( $x, $y ) {
		$this->prop = $x;
		echo $this->prop; // Caused by 13, 21, and possibly 15 and 22
		$this->prop = $y;
		echo $this->prop; // Caused by 15, 22, and possibly 13 and 21
	}
}

$class = new CausedByDifferentParams();
$class->sinkTwoParams( $_GET['a'], '' ); // Caused by 14, 13
$class->sinkTwoParams( '', $_GET['a'] ); // Caused by 16, 15
