<?php

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


function returnTainted( $x ) {
	return $_GET['a'];
}
echo returnTainted( 'foo' ); // Unsafe

function returnTaintedParam( $x ) {
	$x .= $_GET['x'];
	return $x;
}
echo returnTaintedParam( 'foo' ); // Unsafe
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
echo differentTaintsForDifferentParams( 'a', 'b' ); // Unsafe
echo differentTaintsForDifferentParams( $_GET['a'], $_GET['b'] ); // Unsafe
echo differentTaintsForDifferentParams( $_GET['a'], 'b' ); // Unsafe
echo differentTaintsForDifferentParams( 'a', $_GET['b'] ); // Unsafe
echo differentTaintsForDifferentParams( getHTML(), getHTML() ); // Unsafe
echo differentTaintsForDifferentParams( getHTML(), getShell() ); // Unsafe
echo differentTaintsForDifferentParams( getShell(), getHTML() ); // Unsafe
echo differentTaintsForDifferentParams( getShell(), getShell() ); // Unsafe
shell_exec( differentTaintsForDifferentParams( 'a', 'b' ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( $_GET['a'], $_GET['b'] ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( $_GET['a'], 'b' ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( 'a', $_GET['b'] ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( getHTML(), getHTML() ) ); // TODO Unsafe
shell_exec( differentTaintsForDifferentParams( getHTML(), getShell() ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( getShell(), getHTML() ) ); // Unsafe
shell_exec( differentTaintsForDifferentParams( getShell(), getShell() ) ); // Unsafe


function returnHtmlTaintedParam( $x ) {
	$x .= getHTML();
	return $x;
}
echo returnHtmlTaintedParam( 'foo' ); // Unsafe
echo returnHtmlTaintedParam( getHTML() ); // Unsafe
echo returnHtmlTaintedParam( getShell() ); // Unsafe
shell_exec( returnHtmlTaintedParam( 'foo' ) ); // Safe
shell_exec( returnHtmlTaintedParam( getHTML() ) ); // Safe
shell_exec( returnHtmlTaintedParam( getShell() ) ); // Unsafe


function returnHtmlAppended( $x ) {
	return $x . getHTML();
}
echo returnHtmlAppended( 'foo' ); // Unsafe
echo returnHtmlAppended( getHTML() ); // Unsafe
echo returnHtmlAppended( getShell() ); // Unsafe
shell_exec( returnHtmlAppended( 'foo' ) ); // Safe
shell_exec( returnHtmlAppended( getHTML() ) ); // Safe
shell_exec( returnHtmlAppended( getShell() ) ); // Unsafe


function returnHtmlEscapedParam( $x ) {
	$y = htmlspecialchars( $x );
	return $y;
}
echo returnHtmlEscapedParam( 'foo' ); // Safe
echo returnHtmlEscapedParam( getHTML() ); // Safe
echo returnHtmlEscapedParam( getShell() ); // Safe
shell_exec( returnHtmlEscapedParam( 'foo' ) ); // Safe
shell_exec( returnHtmlEscapedParam( getHTML() ) ); // Safe
shell_exec( returnHtmlEscapedParam( getShell() ) ); // TODO Unsafe

function returnHtmlEscapedParam2( $x ) {
	$x = htmlspecialchars( $x );
	return $x;
}
echo returnHtmlEscapedParam2( 'foo' ); // Safe
echo returnHtmlEscapedParam2( getHTML() ); // Safe
echo returnHtmlEscapedParam2( getShell() ); // Safe
shell_exec( returnHtmlEscapedParam2( 'foo' ) ); // Safe
shell_exec( returnHtmlEscapedParam2( getHTML() ) ); // Safe
shell_exec( returnHtmlEscapedParam2( getShell() ) ); // TODO Unsafe


function escapeHtmlFirstShellSecond( $x, $y ) {
	$x = htmlspecialchars( $x );
	$y = escapeshellcmd( $y );
	return $x . $y;
}
echo escapeHtmlFirstShellSecond( 'a', 'b' ); // Safe
echo escapeHtmlFirstShellSecond( $_GET['a'], $_GET['b'] ); // TODO Unsafe
echo escapeHtmlFirstShellSecond( $_GET['a'], 'b' ); // Safe
echo escapeHtmlFirstShellSecond( 'a', $_GET['b'] ); // TODO Unsafe
echo escapeHtmlFirstShellSecond( getHTML(), getHTML() ); // TODO Unsafe
echo escapeHtmlFirstShellSecond( getHTML(), getShell() ); // Safe
echo escapeHtmlFirstShellSecond( getShell(), getHTML() ); // TODO Unsafe
echo escapeHtmlFirstShellSecond( getShell(), getShell() ); // Safe
shell_exec( escapeHtmlFirstShellSecond( 'a', 'b' ) ); // Safe
shell_exec( escapeHtmlFirstShellSecond( $_GET['a'], $_GET['b'] ) ); // TODO Unsafe
shell_exec( escapeHtmlFirstShellSecond( $_GET['a'], 'b' ) ); // TODO Unsafe
shell_exec( escapeHtmlFirstShellSecond( 'a', $_GET['b'] ) ); // Safe
shell_exec( escapeHtmlFirstShellSecond( getHTML(), getHTML() ) ); // Safe
shell_exec( escapeHtmlFirstShellSecond( getHTML(), getShell() ) ); // Safe
shell_exec( escapeHtmlFirstShellSecond( getShell(), getHTML() ) ); // TODO Unsafe
shell_exec( escapeHtmlFirstShellSecond( getShell(), getShell() ) ); // TODO Unsafe


function preserveDifferentDims( $x, $y ) {
	$f = [ 'x' => $x ];
	$s = [ 'y' => $y ];
	return $f + $s;
}
echo preserveDifferentDims( 'safe', 'safe' )['x']; // Safe
echo preserveDifferentDims( 'safe', 'safe' )['y']; // Safe
echo preserveDifferentDims( 'safe', $_GET['unsafe'] )['x']; // Safe
echo preserveDifferentDims( 'safe', $_GET['unsafe'] )['y']; // Unsafe
echo preserveDifferentDims( $_GET['unsafe'], 'safe' )['x']; // Unsafe
echo preserveDifferentDims( $_GET['unsafe'], 'safe' )['y']; // Safe
echo preserveDifferentDims( $_GET['unsafe'], $_GET['unsafe'] )['x']; // Unsafe
echo preserveDifferentDims( $_GET['unsafe'], $_GET['unsafe'] )['y']; // Unsafe


function preservePar( $x ) {
	return $x;
}

function nestedPreserve( $p ) {
	return preservePar( $p );
}
echo nestedPreserve( $_GET['a'] ); // TODO Unsafe
echo nestedPreserve( getHTML() ); // TODO Unsafe
echo nestedPreserve( getShell() ); // Safe

function preserveEscapedPar( $x ) {
	return htmlspecialchars( $x );
}

function nestedPreserveEscaped( $p ) {
	return preserveEscapedPar( $p );
}
echo nestedPreserveEscaped( $_GET['a'] ); // Safe
echo nestedPreserveEscaped( getHTML() ); // Safe
echo nestedPreserveEscaped( getShell() ); // Safe
shell_exec( nestedPreserveEscaped( $_GET['a'] ) ); // TODO Unsafe
shell_exec( nestedPreserveEscaped( getHTML() ) ); // Safe
shell_exec( nestedPreserveEscaped( getShell() ) ); // TODO Unsafe

function nestedPreserveEscaped2( $p ) {
	return htmlspecialchars( preservePar( $p ) );
}
echo nestedPreserveEscaped2( $_GET['a'] ); // Safe
echo nestedPreserveEscaped2( getHTML() ); // Safe
echo nestedPreserveEscaped2( getShell() ); // Safe
shell_exec( nestedPreserveEscaped2( $_GET['a'] ) ); // TODO Unsafe
shell_exec( nestedPreserveEscaped2( getHTML() ) ); // Safe
shell_exec( nestedPreserveEscaped2( getShell() ) ); // TODO Unsafe

function preserveMovedAtOffset( $par ) {
	return [ 'x' => $par, 'z' => 'safe' ];
}
echo preserveMovedAtOffset( $_GET['a'] ); // Unsafe
echo preserveMovedAtOffset( $_GET['a'] )['x']; // Unsafe
echo preserveMovedAtOffset( $_GET['a'] )['z']; // TODO Safe
echo preserveMovedAtOffset( [ 'x' => 'safe', 'z' => $_GET['a'] ] ); // Unsafe
echo preserveMovedAtOffset( [ 'x' => 'safe', 'z' => $_GET['a'] ] )['x']; // Unsafe
echo preserveMovedAtOffset( [ 'x' => 'safe', 'z' => $_GET['a'] ] )['z']; // TODO Safe

function preservePartialMovedAtOffset( $par ) {
	return [ 'x' => $par['y'], 'z' => 'safe' ];
}
echo preservePartialMovedAtOffset( $_GET['a'] ); // Unsafe
echo preservePartialMovedAtOffset( $_GET['a'] )['x']; // TODO Unsafe
echo preservePartialMovedAtOffset( $_GET['a'] )['z']; // Safe
echo preservePartialMovedAtOffset( [ 'y' => 'safe', 'z' => $_GET['a'] ] ); // Safe
echo preservePartialMovedAtOffset( [ 'y' => 'safe', 'z' => $_GET['a'] ] )['x']; // Safe
echo preservePartialMovedAtOffset( [ 'y' => 'safe', 'z' => $_GET['a'] ] )['z']; // Safe
echo preservePartialMovedAtOffset( [ 'z' => 'safe', 'y' => $_GET['a'] ] ); // Unsafe
echo preservePartialMovedAtOffset( [ 'z' => 'safe', 'y' => $_GET['a'] ] )['x']; // TODO Unsafe
echo preservePartialMovedAtOffset( [ 'z' => 'safe', 'y' => $_GET['a'] ] )['z']; // Safe

function preservePartial( $x ) {
	return $x['y'];
}
echo preservePartial( $_GET['a'] ); // Unsafe
echo preservePartial( getHTML() ); // Unsafe
echo preservePartial( getShell() ); // Safe
echo preservePartial( [ 'y' => getHTML(), 'z' => 'safe' ] ); // Unsafe
echo preservePartial( [ 'z' => getHTML(), 'y' => 'safe' ] ); // Safe
echo preservePartial( [ 'y' => getShell(), 'z' => 'safe' ] ); // Safe
echo preservePartial( [ 'y' => [ 'y' => getHTML(), 'x' => 'safe' ], 'z' => 'safe' ] )['y']; // Unsafe
echo preservePartial( [ 'y' => [ 'y' => 'safe', 'x' => getHTML() ], 'z' => 'safe' ] )['y']; // TODO Safe
echo preservePartial( [ 'y' => [ 'y' => getHTML(), 'x' => 'safe' ], 'z' => 'safe' ] )['x']; // Safe
echo preservePartial( [ 'y' => [ 'y' => 'safe', 'x' => getHTML() ], 'z' => 'safe' ] )['x']; // TODO Unsafe
