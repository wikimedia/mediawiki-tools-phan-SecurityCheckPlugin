<?php

function foobar( $par ) {
	$value = $par;
	if ( is_null( $value ) ) {
		$value = '';
	}
	$value = htmlspecialchars( $value ); // This is not double escaped

	return $value;
}

$y = $_GET['foo'];
$y = getEscaped( $y );
echo $y;

$z = $_GET['baz'];
// These _are_ double escaped
getEscaped( htmlspecialchars( $z ) );
getEscaped2( htmlspecialchars( $z ) );
// This is safe because it doesn't use the arg
getEscaped3( htmlspecialchars( $z ) );

function getEscaped( $x ) {
	$x = htmlspecialchars( $x ); // Safe assignment
	return $x;
}

function getEscaped2( $x ) {
	$y = htmlspecialchars( $x );
	$x = $y; // Safe assignment
	return $x;
}

function getEscaped3( $x ) {
	$x = '';
	$x = htmlspecialchars( $x ); // Safe assignment
	return $x;
}

function logFormatter() {
	makePageLink( htmlspecialchars( $_GET['baz'] ) );
}

function makePageLink( $html ) {
	$html = $_GET['foo'];
	echo htmlspecialchars( $html ); // Definitely safe
}

function logFormatter2() {
	makePageLink2( htmlspecialchars( $_GET['baz'] ) );
}

function makePageLink2( $html ) {
	$html = $html; // This must not clear the taint!
	echo htmlspecialchars( $html );
}

function logFormatter3() {
	makePageLink3( htmlspecialchars( $_GET['baz'] ) );
}

function makePageLink3( $html ) {
	$html = rand() ? $html : ''; // This must not clear the taint!
	echo htmlspecialchars( $html );
}

function logFormatter4() {
	makePageLink4( htmlspecialchars( $_GET['baz'] ) );
}

function makePageLink4( $html ) {
	list( $html ) = [ $html ]; // This must not clear the taint!
	echo htmlspecialchars( $html );
}

function logFormatter5() {
	makePageLink5( htmlspecialchars( $_GET['baz'] ) );
}

function makePageLink5( $html ) {
	list( $_, $html, $_ ) = [ 'foo', $html, 'bar' ]; // This must not clear the taint!
	echo htmlspecialchars( $html );
}

function logFormatter6() {
	makePageLink6( htmlspecialchars( $_GET['baz'] ) );
}

function makePageLink6( $html ) {
	list( $_, $html, $_ ) = rand() ? [ 'foo', $html, 'bar' ] : [ '', '', '' ]; // This must not clear the taint!
	echo htmlspecialchars( $html );
}
