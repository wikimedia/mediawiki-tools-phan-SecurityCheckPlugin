<?php

function returnTainted( $x ) {
	return $_GET['a'];
}
echo returnTainted( 'foo' ); // Unsafe

function returnTaintedParam( $x ) {
	$x .= $_GET['x'];
	return $x;
}
echo returnTaintedParam( 'foo' ); // TODO Unsafe
echo returnTaintedParam( $_GET['x'] ); // Unsafe

function returnAppended( $x ) {
	return $x . $_GET['a'];
}
echo returnAppended( 'foo' ); // Unsafe
echo returnAppended( $_GET['x'] ); // Unsafe


class DifferentLinks {
	private $prop;
	function propSetter( $x ) {
		$this->prop = $x . $_GET['a'];
	}
	function propGetter() {
		return $this->prop;// The prop has links and taintedness, but not for this function.
	}
}
$obj = new DifferentLinks();
$obj->propSetter( 'foo' );
echo $obj->propGetter(); // Unsafe
$obj->propSetter( $_GET['a'] );
echo $obj->propGetter(); // Unsafe

function differentTaintsForDifferentParams( $x, $y ) {
	$x .= getHTML();
	$y .= getShell();
	return $x . $y;// This should set HTML on $x and SHELL on $y, not HTML|SHELL on both
}
echo differentTaintsForDifferentParams( 'a', 'b' ); // TODO Unsafe
echo differentTaintsForDifferentParams( $_GET['a'], $_GET['b'] ); // Unsafe
echo differentTaintsForDifferentParams( $_GET['a'], 'b' ); // Unsafe
echo differentTaintsForDifferentParams( 'a', $_GET['b'] ); // Unsafe
echo differentTaintsForDifferentParams( getHTML(), getHTML() ); // Unsafe
echo differentTaintsForDifferentParams( getHTML(), getShell() ); // Unsafe
echo differentTaintsForDifferentParams( getShell(), getHTML() ); // Unsafe
echo differentTaintsForDifferentParams( getShell(), getShell() ); // TODO Unsafe
shell_exec( differentTaintsForDifferentParams( 'a', 'b' ) ); // TODO Unsafe
shell_exec( differentTaintsForDifferentParams( $_GET['a'], $_GET['b'] ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( $_GET['a'], 'b' ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( 'a', $_GET['b'] ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( getHTML(), getHTML() ) ); // TODO Unsafe
shell_exec( differentTaintsForDifferentParams( getHTML(), getShell() ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( getShell(), getHTML() ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( getShell(), getShell() ) ); // Unsafe





// Helpers
/**
 * @return-taint html
 */
function getHTML() {
	return 'x';
}

/**
 * @return-taint shell
 */
function getShell() {
	return 'x';
}
