<?php

// In these concats, we try not to include safe lines in the caused-by (by special-casing).
function withAssignOp() {
	$s = '';

	if ( rand() ) {
		$s .= 'safe';
		$s .= $_GET['x'];
		$s .= 'safe';
	}

	if ( rand() ) {
		$s .= 'safe';
		$s .= $_GET['x'];
		$s .= 'safe';
	}
	if ( rand() ) {
		echo $s;
	}
}

// We cannot do the same with assignments, because the RHS may be more complicated than this.
function withAssign() {
	$s = '';

	if ( rand() ) {
		$s = $s . 'safe';
		$s = $s . $_GET['x'];
		$s = $s . 'safe';
	}

	if ( rand() ) {
		$s = $s . 'safe';
		$s = $s . $_GET['x'];
		$s = $s . 'safe';
	}
	if ( rand() ) {
		echo $s;
	}
}
